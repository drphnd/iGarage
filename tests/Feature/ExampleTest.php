<?php

use App\Models\User;

test('tamu yang membuka beranda diarahkan ke login', function () {
    $this->get('/')->assertRedirect(route('login'));
});

test('halaman login bisa dibuka', function () {
    $this->get(route('login'))->assertOk();
});

test('user biasa ditolak saat membuka halaman admin', function () {
    $user = User::factory()->create();   // role default: user

    $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
});

test('admin bisa membuka halaman kelola user', function () {
    $admin = User::factory()->create();
    $admin->role = 'admin';
    $admin->save();

    $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
});