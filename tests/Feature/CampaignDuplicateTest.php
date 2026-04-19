<?php

namespace Tests\Feature;

use App\Models\CampaignRoute;
use App\Models\Campaigns;
use App\Models\RouteAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignDuplicateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_view_duplicate_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $campaign = Campaigns::factory()->create(['name' => 'Original Campaign']);

        $response = $this->actingAs($admin)->get(route('campaigns.create', ['duplicate_from' => $campaign->id]));

        $response->assertOk();
        $response->assertSee('Copy of Original Campaign');
        $response->assertSee('duplicate_from');
    }

    public function test_admin_can_duplicate_campaign_and_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $sourceCampaign = Campaigns::factory()->create(['name' => 'Original Campaign']);
        
        $route1 = CampaignRoute::factory()->create(['campaign_id' => $sourceCampaign->id, 'name' => 'Route 1']);
        $route2 = CampaignRoute::factory()->create(['campaign_id' => $sourceCampaign->id, 'name' => 'Route 2']);

        // Create assignment to verify it doesn't get duplicated
        RouteAssignment::factory()->create([
            'campaign_route_id' => $route1->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin)->post(route('campaigns.store'), [
            'name' => 'Duplicated Campaign',
            'description' => 'A clone of the original',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'duplicate_from' => $sourceCampaign->id,
        ]);

        $response->assertRedirect(route('campaigns.index'));
        
        $this->assertDatabaseHas('campaigns', [
            'name' => 'Duplicated Campaign',
            'description' => 'A clone of the original',
        ]);

        $duplicatedCampaign = Campaigns::where('name', 'Duplicated Campaign')->first();

        // Should have 2 routes
        $this->assertCount(2, $duplicatedCampaign->campaignRoutes);

        // Verify route names copied
        $this->assertDatabaseHas('campaign_routes', [
            'campaign_id' => $duplicatedCampaign->id,
            'name' => 'Route 1',
        ]);
        
        $this->assertDatabaseHas('campaign_routes', [
            'campaign_id' => $duplicatedCampaign->id,
            'name' => 'Route 2',
        ]);

        // Verify assignments weren't copied
        $newRoute1 = CampaignRoute::where('campaign_id', $duplicatedCampaign->id)->where('name', 'Route 1')->first();
        $this->assertNull($newRoute1->assignment);
    }
}
