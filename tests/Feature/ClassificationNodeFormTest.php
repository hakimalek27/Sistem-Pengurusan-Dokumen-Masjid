<?php

use App\Filament\App\Resources\ClassificationNodes\Pages\CreateClassificationNode;
use App\Filament\App\Resources\ClassificationNodes\Pages\EditClassificationNode;
use App\Models\ClassificationNode;
use Filament\Facades\Filament;
use Livewire\Livewire;

/**
 * Regresi §15.2 / peraturan #10 — borang ClassificationNode.
 * Bug LIVE (laravel.log 2026-07-19): Select->relationship('parent','title')
 * ->modifyQueryUsing(...) melemparkan BadMethodCallException — Filament v4 TIADA method
 * berantai modifyQueryUsing() pada Select. Kesan: borang Cipta/Edit Klasifikasi CRASH.
 * Fix: hantar closure skop sebagai argumen ke-3 relationship(). Ujian ini memastikan
 * borang render tanpa ralat + pilihan nod induk kekal diskop tenant (§15.2).
 */
beforeEach(function () {
    $this->mam = makeMosque('MAM', 'mam');
    $this->man = makeMosque('MAN', 'man');
    $this->adminMam = makeMember($this->mam, 'admin_masjid', 'admin@mam.test');
    $this->parentMam = makeNode($this->mam, '500', 'dalaman', 'fungsi');
    $this->parentMan = makeNode($this->man, '600', 'dalaman', 'fungsi');

    Filament::setCurrentPanel(Filament::getPanel('app'));
    Filament::setTenant($this->mam, isQuiet: true);
    $this->actingAs($this->adminMam);
});

afterEach(function () {
    Filament::setTenant(null, isQuiet: true);
});

it('borang Cipta Klasifikasi render tanpa ralat (regresi modifyQueryUsing)', function () {
    Livewire::test(CreateClassificationNode::class)->assertOk();
});

it('borang Edit Klasifikasi render tanpa ralat (regresi modifyQueryUsing)', function () {
    Livewire::test(EditClassificationNode::class, ['record' => $this->parentMam->getRouteKey()])
        ->assertOk();
});

it('cipta nod dengan induk masjid sendiri berjaya', function () {
    Livewire::test(CreateClassificationNode::class)
        ->fillForm([
            'parent_id' => $this->parentMam->id,
            'level' => 'aktiviti',
            'code' => '500-1',
            'title' => 'Aktiviti Ujian',
            'default_sensitivity' => 'dalaman',
            'is_active' => true,
            'sort' => 0,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $node = ClassificationNode::withoutMosqueScope()->where('code', '500-1')->first();
    expect($node)->not->toBeNull()
        ->and($node->mosque_id)->toBe($this->mam->id)
        ->and($node->parent_id)->toBe($this->parentMam->id);
});

it('nod induk dari masjid lain ditolak — skop tenant kekal (§15.2)', function () {
    Livewire::test(CreateClassificationNode::class)
        ->fillForm([
            'parent_id' => $this->parentMan->id, // induk milik tenant LAIN
            'level' => 'aktiviti',
            'code' => '500-2',
            'title' => 'Aktiviti Silang',
            'default_sensitivity' => 'dalaman',
            'is_active' => true,
            'sort' => 0,
        ])
        ->call('create')
        ->assertHasFormErrors(['parent_id']);
});
