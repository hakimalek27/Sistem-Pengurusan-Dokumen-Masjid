<?php

use App\Enums\RetentionAction;
use App\Filament\App\Resources\RetentionRules\Pages\EditRetentionRule;
use App\Models\RetentionRule;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

/**
 * §15.2 — Pengasingan tenant untuk Peraturan Retensi.
 * RetentionRule sengaja TIDAK guna global scope mosque (perlu lihat peraturan platform
 * NULL), jadi skop tenant bergantung pada RetentionRuleResource::getEloquentQuery() +
 * borang yang tidak mendedah mosque_id + guard EditRetentionRule::mutateFormDataBeforeSave.
 * Ujian ini membuktikan admin satu masjid tidak boleh membaca/mengalih peraturan masjid lain.
 */
beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->adminMam = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');

    $this->ruleMam = RetentionRule::query()->create([
        'mosque_id' => $this->mam->id,
        'record_type' => 'surat_menyurat',
        'retain_years' => 7,
        'action' => RetentionAction::AutoPadam,
        'note' => 'asal MAM',
    ]);
    $this->ruleMan = RetentionRule::query()->create([
        'mosque_id' => $this->man->id,
        'record_type' => 'surat_menyurat',
        'retain_years' => 7,
        'action' => RetentionAction::AutoPadam,
        'note' => 'asal MAN',
    ]);

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->mam, isQuiet: true);
    $this->actingAs($this->adminMam);
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
});

it('admin masjid tidak boleh membuka edit peraturan retensi masjid lain (skop getEloquentQuery)', function () {
    expect(fn () => Livewire::test(EditRetentionRule::class, ['record' => $this->ruleMan->getRouteKey()]))
        ->toThrow(ModelNotFoundException::class);
});

it('simpan peraturan retensi kekal terikat tenant walau payload mosque_id diusik', function () {
    Livewire::test(EditRetentionRule::class, ['record' => $this->ruleMam->getRouteKey()])
        ->fillForm(['note' => 'dikemas kini'])
        ->set('data.mosque_id', $this->man->id) // cubaan alih ke tenant lain
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->ruleMam->fresh()->mosque_id)->toBe($this->mam->id)
        ->and($this->ruleMam->fresh()->note)->toBe('dikemas kini')
        ->and($this->ruleMan->fresh()->mosque_id)->toBe($this->man->id);
});
