<?php

namespace AloongJerr\Accounting\Filament\Resources\JournalResource\Pages;

use AloongJerr\Accounting\Accounting;
use AloongJerr\Accounting\Filament\Resources\JournalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJournal extends CreateRecord
{
    protected static string $resource = JournalResource::class;

    public function getTitle(): string
    {
        return __('accounting::accounting.resources.journal.pages.create.title');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $entries = $data['entries'] ?? [];
        unset($data['entries']);

        // Use ManualJournal transaction for proper creation
        $journal = Accounting::journal($data['description'])
            ->onDate($data['date'])
            ->comment($data['comments'] ?? '');

        foreach ($entries as $entry) {
            if ($entry['debit'] > 0) {
                $journal->debit($entry['account_id'], $entry['debit'], $entry['description'] ?? null);
            }
            if ($entry['credit'] > 0) {
                $journal->credit($entry['account_id'], $entry['credit'], $entry['description'] ?? null);
            }
        }

        return $journal->commit();
    }
}
