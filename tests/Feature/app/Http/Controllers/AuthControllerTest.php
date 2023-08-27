<?php

namespace Tests\Feature\app\Http\Controllers;

use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    public const BASE_URL = 'api/users';

    public function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->artisan('passport:install');
    }

    public function testShouldCreate()
    {
        $payload = $this->getFakePayload();

        $response = $this->postJson(sprintf('%s/register', self::BASE_URL), $payload);
        $actual = json_decode($response->getContent(), true);
        $this->assertEquals(Arr::only($payload, ['name', 'email']), Arr::only($actual, ['name', 'email']));
        $this->assertEquals(201, $response->getStatusCode());
    }

    public function testShouldNotCreateWhenValidationErrors()
    {
        $payload = [
            'name'         => 'Test Name',
            'email'        => 'teste',
            'password'     => '12345678',
            'c_password'   => '12345678',
            'type_user_id' => 1,
        ];

        $response = $this->postJson(sprintf('%s/register', self::BASE_URL), $payload);
        $actual = json_decode($response->getContent(), true);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertEquals('Email invalido.', Arr::first($actual['errors']['email']));
    }

    public function testShouldNotCreateWhenUserExists()
    {
        $payload = Arr::except($this->getFakePayload(), 'c_password');

        User::factory($payload)->create();

        $response = $this->postJson(sprintf('%s/register', self::BASE_URL), $this->getFakePayload());
        $actual = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('Usuario ja existe', $actual['errors']);
    }

    public function testShouldLogin()
    {
        $payload = $this->getFakePayload();

        $this->postJson(sprintf('%s/register', self::BASE_URL), $payload);

        $user = User::where('email', $payload['email'])->first();
        $response = $this->postJson(sprintf('%s/login', self::BASE_URL), ['email' => $user->email, 'password' => '12345678']);
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function testShouldErrorWithInvalidCredentials()
    {
        $user = User::factory()->create();
        $response = $this->postJson(sprintf('%s/login', self::BASE_URL), ['email' => $user->email, 'password' => '12345678']);
        $this->assertEquals(401, $response->getStatusCode());
        $actual = json_decode($response->getContent(), true);

        $this->assertEquals('Nao autorizado', $actual['errors']);
    }

    public function testShouldLogout()
    {
        $payload = $this->getFakePayload();

        $this->postJson(sprintf('%s/register', self::BASE_URL), $payload);

        $user = User::where('email', $payload['email'])->first();
        $this->postJson(sprintf('%s/login', self::BASE_URL), ['email' => $user->email, 'password' => '12345678']);
        $response = $this->postJson(sprintf('%s/logout', self::BASE_URL));
        $this->assertEquals(204, $response->getStatusCode());
    }

    public function testShouldLogoutWhenUserNotAuthenticate()
    {
        $response = $this->postJson(sprintf('%s/logout', self::BASE_URL));
        $actual = json_decode($response->getContent(), true);
        $this->assertEquals('Usuario não esta logado', $actual['errors']);
    }

    private function getFakePayload(): array
    {
        $typeUser = TypeUser::factory()->create();

        return [
            'name'         => 'Test Name',
            'email'        => 'teste@email.com',
            'password'     => '12345678',
            'c_password'   => '12345678',
            'type_user_id' => $typeUser->id,
        ];
    }
}