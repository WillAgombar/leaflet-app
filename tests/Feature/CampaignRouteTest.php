<?php

namespace Tests\Feature;

use App\Models\CampaignRoute;
use App\Models\Campaigns;
use App\Models\RouteAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_users_can_view_campaign_routes(): void
    {
        $user = User::factory()->create();
        $campaign = Campaigns::factory()->create();
        $route = CampaignRoute::factory()->create(['campaign_id' => $campaign->id]);

        $response = $this->actingAs($user)->get(route('campaigns.show', $campaign));

        $response->assertOk();
        $response->assertSee($route->name);
    }

    public function test_users_can_volunteer_for_unassigned_route(): void
    {
        $user = User::factory()->create();
        $campaign = Campaigns::factory()->create();
        $route = CampaignRoute::factory()->create(['campaign_id' => $campaign->id]);

        $response = $this->actingAs($user)->post(route('campaigns.routes.volunteer', [$campaign, $route]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('route_assignments', [
            'campaign_route_id' => $route->id,
            'user_id' => $user->id,
            'status' => 'assigned',
        ]);
    }

    public function test_users_cannot_volunteer_for_assigned_route(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $campaign = Campaigns::factory()->create();
        $route = CampaignRoute::factory()->create(['campaign_id' => $campaign->id]);
        RouteAssignment::factory()->create([
            'campaign_route_id' => $route->id,
            'user_id' => $user1->id,
            'status' => 'assigned',
        ]);

        $response = $this->actingAs($user2)->post(route('campaigns.routes.volunteer', [$campaign, $route]));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseMissing('route_assignments', [
            'campaign_route_id' => $route->id,
            'user_id' => $user2->id,
        ]);
    }
}
