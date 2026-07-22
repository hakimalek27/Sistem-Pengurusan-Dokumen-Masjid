<?php

namespace App\Filament\Support;

use App\Models\SupportRequest;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class SupportRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Laporan')
                ->schema([
                    TextEntry::make('reference')->label('Rujukan')->copyable(),
                    TextEntry::make('status')->label('Status')->badge(),
                    TextEntry::make('category')->label('Kategori')->badge(),
                    TextEntry::make('created_at')->label('Diterima')->dateTime('d/m/Y H:i:s'),
                    TextEntry::make('mosque.name')->label('Tenant')->placeholder('Awam'),
                    TextEntry::make('user.name')->label('Pelapor')->placeholder('Orang awam'),
                    TextEntry::make('role')->label('Role')->placeholder('Awam'),
                    TextEntry::make('subject')->label('Ringkasan')->columnSpanFull(),
                    TextEntry::make('expected')->label('Hasil Dijangka')->columnSpanFull(),
                    TextEntry::make('actual')->label('Kejadian Sebenar')->columnSpanFull(),
                ])->columns(3),
            Section::make('Konteks Disanitasi')
                ->schema([
                    TextEntry::make('route_template')->label('Route')->placeholder('—'),
                    TextEntry::make('request_id')->label('ID Permintaan')->copyable()->placeholder('—'),
                    KeyValueEntry::make('browser_context')->label('Browser')->columnSpanFull(),
                    TextEntry::make('unmatched_query')->label('Pertanyaan Tanpa Hasil')
                        ->visible(fn (SupportRequest $record): bool => $record->query_consent && filled($record->unmatched_query))
                        ->columnSpanFull(),
                ])->columns(2),
            Section::make('Lampiran')
                ->schema([
                    TextEntry::make('attachment_links')->hiddenLabel()
                        ->state(fn (SupportRequest $record): HtmlString => self::attachments($record))
                        ->html()->columnSpanFull(),
                ]),
        ]);
    }

    protected static function attachments(SupportRequest $request): HtmlString
    {
        $items = $request->attachments->map(function ($attachment): string {
            $name = e($attachment->original_name);
            $url = e(route('support-attachment.show', $attachment));
            $size = number_format($attachment->size_bytes / 1024, 1);

            return "<li><a href=\"{$url}\">{$name}</a> <small>({$size} KB, antivirus: ".e($attachment->scan_status).')</small></li>';
        })->implode('');

        return new HtmlString($items ? "<ul class=\"space-y-2\">{$items}</ul>" : '<p>Tiada lampiran.</p>');
    }
}
