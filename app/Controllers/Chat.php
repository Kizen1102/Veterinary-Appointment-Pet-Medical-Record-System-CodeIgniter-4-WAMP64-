<?php

namespace App\Controllers;

use App\Libraries\VetAssistant;
use App\Models\ChatConversationModel;
use App\Models\ChatMessageModel;
use App\Models\MedicalRecordModel;
use App\Models\PetModel;
use Config\AI;

/**
 * AI Medical Information Chatbot ("PawDoc"): explains vet terms in plain language.
 */
class Chat extends BaseController
{
    // Conversation list + "ask a question" box (GET /chat?pet=ID)
    public function index()
    {
        $userId = session('user')['id'];
        $pets   = (new PetModel())->forOwner($userId);

        // Pet that new questions are about (?pet=ID, only the owner's own pets)
        $selectedPet = null;
        foreach ($pets as $pet) {
            if ((int) $pet['id'] === (int) $this->request->getGet('pet')) {
                $selectedPet = $pet;
            }
        }
        $selectedPet ??= $pets[0] ?? null;

        return view('chat/index', [
            'title'         => 'AI Chat',
            'pets'          => $pets,
            'selectedPet'   => $selectedPet,
            'suggestions'   => $this->suggestions($selectedPet),
            'conversations' => (new ChatConversationModel())
                ->select('chat_conversations.*, pets.name AS pet_name')
                ->join('pets', 'pets.id = chat_conversations.pet_id', 'left')
                ->where('chat_conversations.user_id', $userId)
                ->orderBy('chat_conversations.updated_at', 'DESC')
                ->findAll(20),
        ]);
    }

    // Starts a new conversation with the first question (POST /chat)
    public function start()
    {
        if (! $this->validate(['message' => ['label' => 'Question', 'rules' => 'required|max_length[1000]']])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $userId = session('user')['id'];
        $petId  = (int) $this->request->getPost('pet_id');
        $pet    = $petId > 0 ? (new PetModel())->where('owner_id', $userId)->find($petId) : null;

        $question       = trim($this->request->getPost('message'));
        $conversationId = (new ChatConversationModel())->insert([
            'user_id' => $userId,
            'pet_id'  => $pet['id'] ?? null,
            'title'   => mb_strimwidth($question, 0, 80, '…'),
        ]);

        $this->addQuestionAndReply((int) $conversationId, $question, $pet);

        return redirect()->to('/chat/' . $conversationId);
    }

    // One conversation (GET /chat/<id>)
    public function show(int $id)
    {
        $conversation = $this->findOwnConversation($id);

        if (! $conversation) {
            return redirect()->to('/chat')->with('error', 'Conversation not found.');
        }

        return view('chat/show', [
            'title'        => 'AI Chat',
            'conversation' => $conversation,
            'messages'     => (new ChatMessageModel())->forConversation($id),
        ]);
    }

    // Asks a follow-up question in the same conversation (POST /chat/<id>)
    public function reply(int $id)
    {
        $conversation = $this->findOwnConversation($id);

        if (! $conversation) {
            return redirect()->to('/chat')->with('error', 'Conversation not found.');
        }

        if (! $this->validate(['message' => ['label' => 'Question', 'rules' => 'required|max_length[1000]']])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $pet = $conversation['pet_id'] ? (new PetModel())->find($conversation['pet_id']) : null;
        $this->addQuestionAndReply($id, trim($this->request->getPost('message')), $pet);

        return redirect()->to('/chat/' . $id . '#bottom');
    }

    // Deletes a conversation and its messages (POST /chat/<id>/delete)
    public function delete(int $id)
    {
        if (! $this->findOwnConversation($id)) {
            return redirect()->to('/chat')->with('error', 'Conversation not found.');
        }

        (new ChatConversationModel())->delete($id); // its messages are deleted too (ON DELETE CASCADE)

        return redirect()->to('/chat')->with('success', 'Conversation deleted.');
    }

    /** Saves the owner's question, asks the assistant, and saves the answer. */
    private function addQuestionAndReply(int $conversationId, string $question, ?array $pet): void
    {
        $messages = new ChatMessageModel();
        $messages->insert(['conversation_id' => $conversationId, 'sender' => 'user', 'content' => $question]);

        // The whole conversation (last 20 messages) so the assistant remembers earlier questions
        $history = array_slice($messages->forConversation($conversationId), -20);
        $records = $pet ? (new MedicalRecordModel())->where('pet_id', $pet['id'])->orderBy('visit_date', 'DESC')->findAll(5) : [];

        $answer = (new VetAssistant())->chat($history, $pet ?? [], $records);

        $messages->insert([
            'conversation_id' => $conversationId,
            'sender'          => 'assistant',
            'content'         => $answer['text'],
            'ai_model'        => $answer['source'] === 'ai' ? config(AI::class)->model : 'glossary',
        ]);

        // Moves the conversation to the top of the list
        (new ChatConversationModel())->builder()->where('id', $conversationId)->update(['updated_at' => date('Y-m-d H:i:s')]);
    }

    /** One of the logged-in user's conversations (with the pet name), or null. */
    private function findOwnConversation(int $id): ?array
    {
        return (new ChatConversationModel())
            ->select('chat_conversations.*, pets.name AS pet_name')
            ->join('pets', 'pets.id = chat_conversations.pet_id', 'left')
            ->where('chat_conversations.user_id', session('user')['id'])
            ->find($id);
    }

    /** Quick questions to tap, based on the pet's latest diagnoses. */
    private function suggestions(?array $pet): array
    {
        $suggestions = [];

        if ($pet) {
            $records = (new MedicalRecordModel())->where('pet_id', $pet['id'])
                ->where('diagnosis IS NOT NULL')->orderBy('visit_date', 'DESC')->findAll(2);

            foreach ($records as $record) {
                $suggestions[] = 'What does "' . $record['diagnosis'] . '" mean?';
            }
        }

        return array_merge($suggestions, [
            'What does BID mean on a prescription?',
            'What is a CBC blood test?',
            'Why must I finish all the antibiotics?',
        ]);
    }
}
