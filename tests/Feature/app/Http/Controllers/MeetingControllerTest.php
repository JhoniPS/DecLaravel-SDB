<?php

namespace Tests\Feature\app\Http\Controllers;

use App\Enums\TypeUserEnum;
use App\Models\GroupHasRepresentative;
use App\Models\Meeting;
use App\Models\Group;
use App\Models\TypeUser;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\Feature\Utils\LoginUsersTrait;
use Tests\TestCase;

class MeetingControllerTest extends TestCase
{
    use DatabaseTransactions;
    use LoginUsersTrait;

    const BASE_URL = 'api/meeting-history';

    public function setUp(): void
    {
        $this->faker = FakerFactory::create();
        parent::setUp(); // TODO: Change the autogenerated stub
    }

    public function testShouldListAll()
    {
        $group = Group::factory()->create();
        Meeting::factory(['group_id' => $group->id])->create();
        Meeting::factory(['group_id' => $group->id])->create();

        $this->login(TypeUserEnum::REPRESENTATIVE);

        $response = $this->get(self::BASE_URL);

        $response->assertStatus(200);
        $this->assertCount(2, json_decode($response->getContent(), true));
    }

    public function testShouldListOne()
    {
        $group = Group::factory()->create();
        $meeting = Meeting::factory(['group_id' => $group->id])->create();
        $this->login(TypeUserEnum::REPRESENTATIVE);

        $response = $this->get(sprintf('%s/%s', self::BASE_URL, $meeting->id));

        $response->assertStatus(200);
        $response->assertJsonStructure($this->getJsonStructure());
    }

    public function testNotShouldListOneWhenNotFoundMeeting()
    {
        $this->login(TypeUserEnum::REPRESENTATIVE);

        $response = $this->get(sprintf('%s/%s', self::BASE_URL, 100));

        $response->assertStatus(404);
        $this->assertEquals('Reunião não encontrada', json_decode($response->getContent(), true)['errors']);
    }

    public function testShouldCreate()
    {
        $userRepresentative = $this->login(TypeUserEnum::REPRESENTATIVE);
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $userRepresentative->id])->create();

        $payload = [
            'content' => 'teste teste',
            'summary' => $this->faker->text,
            'ata'     => 'ata numero 20020',
        ];

        $response = $this->post(sprintf('/api/group/%s/meeting-history', $group->id), $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('meetings', array_merge($payload, ['group_id' => $group->id]));
    }

    public function testShouldNotCreateWhenGroupNotFound()
    {
        $userRepresentative = $this->login(TypeUserEnum::REPRESENTATIVE);
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $userRepresentative->id])->create();

        $payload = [
            'content' => 'tetstststs',
            'summary' => $this->faker->text,
            'ata'     => 'ata numero 20',
        ];

        $response = $this->post(sprintf('/api/group/%s/meeting-history', 100), $payload);

        $response->assertStatus(404);
        $this->assertEquals('Grupo não encontrado', json_decode($response->getContent(), true)['errors']);
    }


    public function testShouldNotCreateWhenIsNotTheRepresentativeOfGroup()
    {
        $typeUser = TypeUser::where('name', TypeUserEnum::REPRESENTATIVE)->first();
        $this->login(TypeUserEnum::REPRESENTATIVE);
        $user1 = User::factory(['type_user_id' => $typeUser->id])->create();
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $user1->id])->create();

        $payload = [
            'content' => 'tetstststs',
            'summary' => $this->faker->text,
            'ata'     => 'ata numero 20',
        ];

        $response = $this->post(sprintf('/api/group/%s/meeting-history', $group->id), $payload);

        $response->assertStatus(403);
        $this->assertEquals('This action is unauthorized.', json_decode($response->getContent(), true)['errors']);
    }

    public function testShouldUpdate()
    {
        $userRepresentative = $this->login(TypeUserEnum::REPRESENTATIVE);
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $userRepresentative->id])->create();
        $meeting = Meeting::factory(['group_id' => $group->id])->create();

        $payload = [
            'content' => 'tetstststs',
            'summary' => $this->faker->text,
            'ata'     => 'ata numero 20',
        ];

        $response = $this->put(sprintf('api/meeting-history/%s', $meeting->id), $payload);

        $actual = json_decode($response->getContent(), true);

        $response->assertStatus(200);
        $this->assertEquals($payload, Arr::only($actual, ['content', 'ata', 'summary']));
    }

    public function testShouldNotUpdateWhenMeetingNotFound()
    {
        $userRepresentative = $this->login(TypeUserEnum::REPRESENTATIVE);
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $userRepresentative->id])->create();

        $payload = [
            'summary' => $this->faker->text,
        ];

        $response = $this->put(sprintf('api/meeting-history/%s', 100), $payload);

        $actual = json_decode($response->getContent(), true);

        $response->assertStatus(404);
        $this->assertEquals('Reunião não encontrada', json_decode($response->getContent(), true)['errors']);
    }

    public function testShouldNotUpdateWhenIsNotTheRepresentativeOfGroup()
    {
        $typeUser = TypeUser::where('name', TypeUserEnum::REPRESENTATIVE)->first();
        $this->login(TypeUserEnum::REPRESENTATIVE);
        $user1 = User::factory(['type_user_id' => $typeUser->id])->create();
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $user1->id])->create();
        $meeting = Meeting::factory(['group_id' => $group->id])->create();

        $payload = [
            'summary' => $this->faker->text,
        ];

        $response = $this->put(sprintf('api/meeting-history/%s', $meeting->id), $payload);

        $response->assertStatus(403);
        $this->assertEquals('This action is unauthorized.', json_decode($response->getContent(), true)['errors']);
    }

    public function testShouldDelete()
    {
        $userRepresentative = $this->login(TypeUserEnum::REPRESENTATIVE);
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $userRepresentative->id])->create();
        $meeting = Meeting::factory(['group_id' => $group->id])->create();

        $response = $this->delete(sprintf('api/group/%s/meeting-history/%s', $group->id, $meeting->id));

        $response->assertStatus(204);
        $this->assertDatabaseMissing('meetings', $meeting->toArray());
    }

    public function testShouldNotDeleteWhenGroupNotFound()
    {
        $userRepresentative = $this->login(TypeUserEnum::REPRESENTATIVE);
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $userRepresentative->id])->create();
        $meeting = Meeting::factory(['group_id' => $group->id])->create();

        $response = $this->delete(sprintf('api/group/%s/meeting-history/%s', 100, $meeting->id));

        $response->assertStatus(404);
        $this->assertEquals('Grupo não encontrado', json_decode($response->getContent(), true)['errors']);
    }

    public function testShouldNotDeleteWhenMeetingNotFound()
    {
        $userRepresentative = $this->login(TypeUserEnum::REPRESENTATIVE);
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $userRepresentative->id])->create();

        $response = $this->delete(sprintf('api/group/%s/meeting-history/%s', $group->id, 100));

        $response->assertStatus(404);
        $this->assertEquals('Reunião não encontrada', json_decode($response->getContent(), true)['errors']);
    }

    public function testShouldNotDeleteWhenIsNotTheRepresentativeOfGroup()
    {
        $typeUser = TypeUser::where('name', TypeUserEnum::REPRESENTATIVE)->first();
        $this->login(TypeUserEnum::REPRESENTATIVE);
        $user1 = User::factory(['type_user_id' => $typeUser->id])->create();
        $group = Group::factory()->create();
        GroupHasRepresentative::factory(['group_id' => $group->id, 'user_id' => $user1->id])->create();
        $meeting = Meeting::factory(['group_id' => $group->id])->create();

        $response = $this->delete(sprintf('api/group/%s/meeting-history/%s', $group->id, $meeting->id));

        $response->assertStatus(403);
        $this->assertEquals('This action is unauthorized.', json_decode($response->getContent(), true)['errors']);
    }

    private function getJsonStructure(): array
    {
        return [
            'id',
            'content',
            'summary',
            'ata',
            'group_id',
            'created_at',
            'updated_at',
        ];
    }
}
