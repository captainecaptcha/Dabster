<?php

namespace Tests\Browser;

use Tests\DuskTestCase;
use Laravel\Dusk\Browser;
use Illuminate\Foundation\Testing\DatabaseMigrations;

use App\User;

class RegisterTest extends DuskTestCase
{
    public function test_register_redirects_to_feed()
    {
        $this->browse(function (Browser $browser) {

            $browser->visit('/register')
                ->type('email', 'user@mail.com')
                ->type('pseudo', 'user')
                ->type('password', 'secret')
                ->press('S\'inscrire');
            $userCreated = User::where('pseudo', 'user')->first();

            $browser->assertPathIs('/users/' . $userCreated->id . '/feed')
                ->visit('/logout');

            $userCreated->delete();
        });
    }

    public function test_register_fails_pseudo()
    {
        $this->browse(function (Browser $browser) {

            $userCreated = factory(User::class)->create([
                'pseudo' => 'user'
            ]);

            $browser->visit('/register')
                ->type('email', 'user@mail.com')
                ->type('pseudo', 'user')
                ->type('password', 'secret')
                ->press('S\'inscrire')
                ->assertPathIs('/register');

            $userCreated->delete();
        });
    }

    public function test_register_fails_email()
    {
        $this->browse(function (Browser $browser) {

            $userCreated = factory(User::class)->create([
                'email' => 'user@mail.com'
            ]);

            $browser->visit('/register')
                ->type('email', 'user@mail.com')
                ->type('pseudo', 'user')
                ->type('password', 'secret')
                ->press('S\'inscrire')
                ->assertPathIs('/register');

            $userCreated->delete();
        });
    }
}
