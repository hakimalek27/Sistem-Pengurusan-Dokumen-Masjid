<?php

namespace Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Flush sesi sebelum menukar pengguna. Filament `AuthenticateSession`
     * menyimpan cap kata laluan dalam sesi dan log keluar jika ia berubah;
     * dalam ujian yang `actingAs` beberapa pengguna berlainan berturut (kini
     * semua ada kata laluan), pertukaran itu tersalah anggap sebagai penukaran
     * kata laluan. Artifak ujian sahaja — dalam produksi seorang pengguna per
     * sesi. (Tiada ujian bergantung pada withSession() sebelum actingAs.)
     */
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $this->flushSession();

        return parent::actingAs($user, $guard);
    }
}
