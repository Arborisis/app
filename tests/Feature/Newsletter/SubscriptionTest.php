<?php

declare(strict_types=1);

use App\Models\NewsletterSubscriber;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
});

describe('Newsletter Subscription', function () {
    it('allows a visitor to subscribe with a valid email', function () {
        $response = $this->withSession(['_token' => 'test'])->post(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            '_token' => 'test',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'test@example.com',
            'unsubscribed_at' => null,
        ]);
    });

    it('prevents subscription with an invalid email', function () {
        $response = $this->withSession(['_token' => 'test'])->post(route('newsletter.subscribe'), [
            'email' => 'not-an-email',
            '_token' => 'test',
        ]);

        $response->assertSessionHasErrors('email');
    });

    it('allows a previously unsubscribed user to resubscribe', function () {
        $subscriber = NewsletterSubscriber::factory()->create([
            'email' => 'returning@example.com',
            'unsubscribed_at' => now(),
        ]);

        $response = $this->withSession(['_token' => 'test'])->post(route('newsletter.subscribe'), [
            'email' => 'returning@example.com',
            '_token' => 'test',
        ]);

        $response->assertRedirect();

        $subscriber->refresh();
        expect($subscriber->unsubscribed_at)->toBeNull();
        expect($subscriber->isActive())->toBeTrue();
    });

    it('allows an active subscriber to subscribe again without error', function () {
        NewsletterSubscriber::factory()->create([
            'email' => 'already@example.com',
        ]);

        $response = $this->withSession(['_token' => 'test'])->post(route('newsletter.subscribe'), [
            'email' => 'already@example.com',
            '_token' => 'test',
        ]);

        $response->assertRedirect();

        expect(NewsletterSubscriber::where('email', 'already@example.com')->count())->toBe(1);
    });
});

describe('Newsletter Unsubscription', function () {
    it('allows unsubscribing with a valid token', function () {
        $subscriber = NewsletterSubscriber::factory()->create();

        $response = $this->get(route('newsletter.unsubscribe', ['token' => $subscriber->token]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Newsletter/Unsubscribe')
                ->where('success', true)
            );

        $subscriber->refresh();
        expect($subscriber->unsubscribed_at)->not->toBeNull();
    });

    it('returns false for an invalid token', function () {
        $response = $this->get(route('newsletter.unsubscribe', ['token' => 'invalid-token']));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Newsletter/Unsubscribe')
                ->where('success', false)
            );
    });

    it('returns false for an already unsubscribed user', function () {
        $subscriber = NewsletterSubscriber::factory()->create([
            'unsubscribed_at' => now(),
        ]);

        $response = $this->get(route('newsletter.unsubscribe', ['token' => $subscriber->token]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Newsletter/Unsubscribe')
                ->where('success', false)
            );
    });
});

describe('Newsletter Admin Access', function () {
    it('allows admin to access newsletter resources', function () {
        $admin = User::factory()->create(['role' => \App\Enums\UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/newsletter-campaigns')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/newsletter-subscribers')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/send-newsletter')
            ->assertOk();
    });

    it('allows moderator to access newsletter resources', function () {
        $moderator = User::factory()->create(['role' => \App\Enums\UserRole::Moderator]);

        $this->actingAs($moderator)
            ->get('/admin/newsletter-campaigns')
            ->assertOk();

        $this->actingAs($moderator)
            ->get('/admin/newsletter-subscribers')
            ->assertOk();
    });

    it('restricts send-newsletter page to admin only', function () {
        $moderator = User::factory()->create(['role' => \App\Enums\UserRole::Moderator]);

        $this->actingAs($moderator)
            ->get('/admin/send-newsletter')
            ->assertForbidden();
    });
});
