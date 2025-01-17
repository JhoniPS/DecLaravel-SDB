<?php

namespace Tests\Feature\app\Http\Controllers;

use App\Enums\TypeUserEnum;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Laravel\Passport\Passport;
use Tests\Feature\Utils\LoginUsersTrait;
use Tests\TestCase;

class TypeUserControllerTest extends TestCase
{
    use DatabaseTransactions;
    use LoginUsersTrait;

    public function testIndexTypeUsers()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Cria 10 tipos de usuários no banco de dados usando o model factory
        TypeUser::factory(10)->create();

        // Envia uma solicitação para listar todos os tipos de usuários
        $response = $this->getJson('/api/type-user');
        $actual = json_decode($response->getContent(), true);

        // Verifica se a solicitação foi bem-sucedida e se a resposta contém os tipos de usuários
        $response->assertStatus(200);
        $this->assertEquals(TypeUser::all()->toArray(), $actual);
    }

    /**
     * Teste de sucesso: Verificar se a listagem de tipos de usuários está vazia quando não há nenhum no banco de dados.
     *
     * @return void
     */
    public function testIndexEmptyTypeUsers()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Envia uma solicitação para listar todos os tipos de usuários quando não há nenhum no banco de dados
        $response = $this->getJson('/api/type-user');

        // Verifica se a solicitação foi bem-sucedida e se a resposta está vazia
        $response->assertStatus(200)
            ->assertJson([]);
    }

    public function testShowTypeUser()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Cria um tipo de usuário no banco de dados usando o model factory
        $typeUser = TypeUser::factory()->create();

        // Envia uma solicitação para exibir o tipo de usuário criado
        $response = $this->getJson('/api/type-user/' . $typeUser->id);

        // Verifica se a solicitação foi bem-sucedida e se os dados retornados são corretos
        $response->assertStatus(200)
            ->assertJson([
                             'id'   => $typeUser->id,
                             'name' => $typeUser->name,
                         ]);
    }

    /**
     * Teste de falha: Verificar se um tipo de usuário inexistente retorna um erro 404.
     *
     * @return void
     */
    public function testShowNotExistsTypeUser()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Cria um ID inválido para um tipo de usuário inexistente
        $invalidId = 999;

        // Envia uma solicitação para exibir o tipo de usuário inexistente
        $response = $this->getJson('/api/type-user/' . $invalidId);

        // Verifica se a solicitação retornou um erro 404
        $response->assertStatus(404);
    }

    public function testValidationSuccess()
    {
        $this->login(TypeUserEnum::ADMIN);

        $response = $this->postJson('/api/type-user', [
            'name' => 'Administrador', // Valor válido para o campo 'name'
        ]);

        $response->assertStatus(201)
            ->assertJson([
                             'name' => 'Administrador',
                         ]);
    }

    public function testValidationFailedMissingName()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Tenta criar um tipo de usuário sem fornecer o campo 'name'
        $response = $this->postJson('/api/type-user', []);

        // Verifica se a solicitação falhou devido à validação
        $response->assertStatus(422);
    }

    public function testValidationFailedInvalidDataType()
    {
        $this->login(TypeUserEnum::ADMIN);

        $response = $this->postJson('/api/type-user', [
            'name' => 123,
        ]);

        // Verifica se a resposta JSON contém o fragmento de erro esperado
        $response->assertStatus(422);
    }

    public function testValidationFailedNameTooShort()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Dados inválidos para o campo 'name' (menos de 4 caracteres)
        $response = $this->postJson('/api/type-user', [
            'name' => 'abc',
        ]);

        // Verifica se a resposta JSON contém o fragmento de erro esperado
        $response->assertStatus(422);
    }

    public function testValidationFailedOnUpdate()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Dados inválidos para o campo 'name' (menos de 4 caracteres)
        $data = [
            'name' => 'abc',
        ];

        // Obtenha um tipo de usuário existente do banco de dados
        $typeUser = TypeUser::factory()->create();

        $response = $this->putJson('/api/type-user/' . $typeUser->id, $data);

        // Verifica se a solicitação falhou devido à validação
        $response->assertStatus(422);

        // Verifica se o modelo TypeUser não foi atualizado após a solicitação falhada
        $typeUser->refresh();
        $this->assertNotEquals($typeUser->name, $data['name']);
    }

    /**
     * Teste de sucesso: Atualizar o tipo de usuário com um valor válido para o campo 'name'.
     *
     * @return void
     */
    public function testUpdateSuccess()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Cria um tipo de usuário no banco de dados
        $typeUser = TypeUser::factory()->create();

        // Dados válidos para o campo 'name'
        $data = [
            'name' => 'Novo Nome',
        ];

        $response = $this->putJson('/api/type-user/' . $typeUser->id, $data);

        // Verifica se a solicitação foi bem-sucedida
        $response->assertStatus(200);

        // Verifica se o modelo TypeUser foi atualizado corretamente
        $typeUser->refresh();
        $this->assertEquals($typeUser->name, $data['name']);
    }

    public function testDestroyTypeUser()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Cria um tipo de usuário no banco de dados usando o model factory
        $typeUser = TypeUser::factory()->create();

        // Envia uma solicitação para excluir o tipo de usuário criado
        $response = $this->deleteJson('/api/type-user/' . $typeUser->id);

        // Verifica se a solicitação foi bem-sucedida e se a resposta está vazia (204)
        $response->assertStatus(204);

        // Verifica se o tipo de usuário foi removido corretamente do banco de dados
        $this->assertDatabaseMissing('type_users', ['id' => $typeUser->id]);
    }

    /**
     * Teste de falha: Verificar se a exclusão de um tipo de usuário inexistente retorna um erro 404.
     *
     * @return void
     */
    public function testDestroyNonExistingTypeUser()
    {
        $this->login(TypeUserEnum::ADMIN);
        // Cria um ID inválido para um tipo de usuário inexistente
        $invalidId = 999;

        // Envia uma solicitação para excluir o tipo de usuário inexistente
        $response = $this->deleteJson('/api/type-user/' . $invalidId);

        // Verifica se a solicitação retornou um erro 404
        $response->assertStatus(404);
    }

    public function testShouldNotListWithoutPermission()
    {
        $this->login(TypeUserEnum::VIEWER);
        TypeUser::factory(10)->create();

        $response = $this->getJson('/api/type-user');
        $response->assertStatus(403);
    }

    public function testShouldNotListOneWithoutPermission()
    {
        $this->login(TypeUserEnum::VIEWER);
        TypeUser::factory(10)->create();

        $response = $this->getJson(sprintf('/api/type-user/%s', 1));
        $response->assertStatus(403);
    }

    public function testShouldNotUpdateWithoutPermission()
    {
        $data = [
            'name' => 'Novo Nome',
        ];

        $this->login(TypeUserEnum::VIEWER);
        TypeUser::factory(10)->create();

        $response = $this->put(sprintf('/api/type-user/%s', 1), $data);
        $response->assertStatus(403);
    }

    public function testShouldNotDestroyWithoutPermission()
    {
        $this->login(TypeUserEnum::VIEWER);
        TypeUser::factory(10)->create();

        $response = $this->getJson(sprintf('/api/type-user/%s', 1));
        $response->assertStatus(403);
    }

    public function testShouldNotDestroyWhenConnectedToUser()
    {
        $this->login(TypeUserEnum::ADMIN);

        // Cria um tipo de usuário no banco de dados usando o model factory
        $user = User::factory()->create();

        // Envia uma solicitação para excluir o tipo de usuário criado
        $response = $this->deleteJson('/api/type-user/' . $user->type_user_id);

        $response->assertStatus(400);
    }
}
