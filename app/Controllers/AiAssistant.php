<?php

namespace App\Controllers;

use App\Libraries\VetAssistant;
use App\Models\PetModel;

/**
 * Self-service AI symptom checker.
 */
class AiAssistant extends BaseController
{
    public function symptomChecker()
    {
        return view('ai/symptom_checker', [
            'title'     => 'AI Symptom Checker',
            'pets'      => (new PetModel())->withOwner($this->isOwner() ? $this->userId() : null),
            'aiEnabled' => (new VetAssistant())->isEnabled(),
            'result'    => null,
        ]);
    }

    public function checkSymptoms()
    {
        if (! $this->validate(['symptoms' => 'required|min_length[5]|max_length[2000]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $pet   = [];
        $petId = (int) $this->request->getPost('pet_id');
        if ($petId) {
            $found = (new PetModel())->find($petId);
            if ($found && (! $this->isOwner() || (int) $found['owner_id'] === $this->userId())) {
                $pet = $found;
            }
        }

        $assistant = new VetAssistant();

        return view('ai/symptom_checker', [
            'title'     => 'AI Symptom Checker',
            'pets'      => (new PetModel())->withOwner($this->isOwner() ? $this->userId() : null),
            'aiEnabled' => $assistant->isEnabled(),
            'result'    => $assistant->triage((string) $this->request->getPost('symptoms'), $pet),
            'pet'       => $pet,
        ]);
    }
}
