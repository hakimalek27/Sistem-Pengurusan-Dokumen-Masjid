<?php

namespace App\Filament\App\Resources\Records\Schemas;

use App\Models\Record;
use App\Services\SecureDownloadUrl;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class RecordInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Rekod')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Maklumat')
                            ->schema([
                                TextEntry::make('title')->label('Tajuk'),
                                TextEntry::make('record_type')->label('Jenis')
                                    ->formatStateUsing(fn ($state) => config("record_types.{$state}.label", $state))->badge(),
                                TextEntry::make('registryFile.file_no')->label('Fail')->placeholder('—'),
                                TextEntry::make('enclosure_no')->label('No. Kandungan')->placeholder('—'),
                                TextEntry::make('our_ref')->label('Ruj. Kami')->placeholder('—'),
                                TextEntry::make('their_ref')->label('Ruj. Tuan')->placeholder('—'),
                                TextEntry::make('record_date')->label('Tarikh Rekod')->date('d/m/Y')->placeholder('—'),
                                TextEntry::make('sender_name')->label('Pengirim')->placeholder('—'),
                                TextEntry::make('sensitivity')->label('Sensitiviti')->badge(),
                                TextEntry::make('status')->label('Status')->badge(),
                                KeyValueEntry::make('metadata')->label('Medan Khusus Jenis')->columnSpanFull(),
                            ])
                            ->columns(2),
                        Tab::make('Teks OCR')
                            ->schema([
                                TextEntry::make('ocr_status')->label('Status OCR')->badge(),
                                TextEntry::make('ocr_text')->label('Teks Diekstrak')
                                    ->placeholder('Belum ada teks OCR.')
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Lampiran & Versi')
                            ->schema([
                                TextEntry::make('_lampiran')->hiddenLabel()
                                    ->state(fn (Record $record) => self::mediaLinks($record))
                                    ->html()
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Minit')
                            ->schema([
                                RepeatableEntry::make('minits')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('fromUser.name')->label('Daripada'),
                                        TextEntry::make('parent_id')->label('Bebenang')
                                            ->formatStateUsing(fn ($state) => $state ? 'Balasan kepada minit #'.$state : 'Minit asal'),
                                        TextEntry::make('priority')->label('Keutamaan')->badge(),
                                        TextEntry::make('due_at')->label('Tarikh Akhir')->date('d/m/Y')->placeholder('—'),
                                        TextEntry::make('status')->label('Status')->badge(),
                                        TextEntry::make('created_at')->label('Dihantar')->dateTime('d/m/Y H:i'),
                                        TextEntry::make('recipients_list')->label('Penerima')
                                            ->state(fn ($record) => $record->recipients()->with('user')->get()
                                                ->map(fn ($recipient) => ($recipient->jenis === 'tindakan' ? 'Tindakan: ' : 's.k.: ').$recipient->user?->name)
                                                ->filter()->join(', '))
                                            ->columnSpanFull(),
                                        TextEntry::make('body')->label('Catatan')->columnSpanFull(),
                                    ])
                                    ->columns(3),
                            ]),
                        Tab::make('Kelulusan')
                            ->schema([
                                RepeatableEntry::make('approvals')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('requestedBy.name')->label('Pemohon'),
                                        TextEntry::make('approver.name')->label('Pelulus'),
                                        TextEntry::make('status')->label('Status')->badge(),
                                        TextEntry::make('created_at')->label('Dimohon')->dateTime('d/m/Y H:i'),
                                        TextEntry::make('decided_at')->label('Diputuskan')->dateTime('d/m/Y H:i')->placeholder('—'),
                                        TextEntry::make('decision_ip')->label('IP Keputusan')->placeholder('—'),
                                        TextEntry::make('request_note')->label('Nota Permohonan')->placeholder('—')->columnSpanFull(),
                                        TextEntry::make('decision_note')->label('Nota Keputusan')->placeholder('—')->columnSpanFull(),
                                    ])
                                    ->columns(3),
                            ]),
                        Tab::make('Audit')
                            ->schema([
                                RepeatableEntry::make('activities')
                                    ->hiddenLabel()
                                    ->schema([
                                        TextEntry::make('description')->label('Tindakan'),
                                        TextEntry::make('causer.name')->label('Oleh')->placeholder('Sistem'),
                                        TextEntry::make('created_at')->label('Masa')->dateTime('d/m/Y H:i:s'),
                                        KeyValueEntry::make('properties')->label('Butiran')->columnSpanFull(),
                                    ])
                                    ->columns(3),
                            ]),
                    ]),
            ]);
    }

    protected static function mediaLinks(Record $record): HtmlString
    {
        $url = app(SecureDownloadUrl::class);
        $html = '<div class="space-y-3">';
        $count = 0;

        foreach (['original' => 'Fail Asal', 'derived' => 'PDF Boleh Cari', 'attachments' => 'Lampiran'] as $collection => $label) {
            foreach ($record->getMedia($collection) as $media) {
                $count++;
                $previewable = $media->mime_type === 'application/pdf' || str_starts_with((string) $media->mime_type, 'image/');
                $html .= '<div class="rounded-lg border border-gray-200 p-3 dark:border-white/10">'
                    .'<div class="font-medium">'.e($label.': '.$media->file_name).'</div>'
                    .'<div class="mt-2 flex gap-3 text-sm">';

                if ($previewable) {
                    $html .= '<a class="text-primary-600 underline" target="_blank" rel="noopener" href="'.e($url->media($media, 'inline')).'">Pratonton</a>';
                }

                $html .= '<a class="text-primary-600 underline" href="'.e($url->media($media, 'attachment')).'">Muat Turun</a>'
                    .'</div></div>';
            }
        }

        if ($count === 0) {
            $html .= '<p class="text-gray-500">Tiada fail tersedia.</p>';
        }

        return new HtmlString($html.'</div>');
    }
}
