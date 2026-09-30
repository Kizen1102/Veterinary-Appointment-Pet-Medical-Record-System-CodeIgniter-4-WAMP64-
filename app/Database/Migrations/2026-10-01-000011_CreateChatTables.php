<?php

namespace App\Database\Migrations;

use App\Database\PawMigration;

/**
 * chat_conversations / chat_messages — AI Medical Information Chatbot ("PawDoc AI").
 */
class CreateChatTables extends PawMigration
{
    public function up(): void
    {
        $this->forge->addField($this->id() + [
            'user_id'           => $this->fk(),
            'pet_id'            => $this->fk(true),
            'medical_record_id' => $this->fk(true),
            'title'             => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
        ] + $this->timestamps());

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['user_id', 'updated_at'], false, false, 'idx_conv_user_updated');
        $this->forge->addKey('pet_id', false, false, 'idx_conv_pet');
        $this->forge->addKey('medical_record_id', false, false, 'idx_conv_record');
        $this->foreignKey('user_id', 'users', 'CASCADE', 'fk_conv_user');
        $this->foreignKey('pet_id', 'pets', 'SET NULL', 'fk_conv_pet');
        $this->foreignKey('medical_record_id', 'medical_records', 'SET NULL', 'fk_conv_record');
        $this->createTableWithEngine('chat_conversations');

        $this->forge->addField($this->id() + [
            'conversation_id' => $this->fk(),
            'sender'          => ['type' => 'ENUM', 'constraint' => ['user', 'assistant']],
            'content'         => ['type' => 'TEXT'],
            'ai_model'        => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'input_tokens'    => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'output_tokens'   => ['type' => 'INT', 'unsigned' => true, 'null' => true],
        ] + $this->timestamps(false));

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['conversation_id', 'created_at'], false, false, 'idx_msg_conv_created');
        $this->foreignKey('conversation_id', 'chat_conversations', 'CASCADE', 'fk_msg_conv');
        $this->createTableWithEngine('chat_messages');
    }

    public function down(): void
    {
        $this->forge->dropTable('chat_messages', true);
        $this->forge->dropTable('chat_conversations', true);
    }
}
