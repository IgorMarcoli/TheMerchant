<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SellerProfile;
use App\Models\Game;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuários de Demonstração
        $admin = User::create([
            'name'              => 'Administrador TheMerchant',
            'email'             => 'admin@themerchant.local',
            'password'          => Hash::make('admin123456'),
            'role'              => 'admin',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        $seller = User::create([
            'name'              => 'Igor Marcoli (Vendedor Pro)',
            'email'             => 'vendedor@themerchant.local',
            'password'          => Hash::make('vendedor123456'),
            'role'              => 'seller',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        SellerProfile::create([
            'user_id'          => $seller->id,
            'bio'              => 'Vendedor verificado especializado em itens raros de CS2 e coaching de Valorant. Entregas em até 15 minutos via Trade URL.',
            'reputation_score' => 4.95,
            'total_reviews'    => 128,
            'total_sales'      => 342,
        ]);

        $buyer = User::create([
            'name'              => 'João Pedro (Comprador)',
            'email'             => 'comprador@themerchant.local',
            'password'          => Hash::make('comprador123456'),
            'role'              => 'buyer',
            'status'            => 'active',
            'email_verified_at' => now(),
        ]);

        // 2. Jogos Suportados
        $cs2 = Game::create([
            'name'        => 'Counter-Strike 2',
            'slug'        => 'cs2',
            'cover_image' => 'games/cs2.jpg',
            'active'      => true,
        ]);

        $valorant = Game::create([
            'name'        => 'Valorant',
            'slug'        => 'valorant',
            'cover_image' => 'games/valorant.jpg',
            'active'      => true,
        ]);

        $lol = Game::create([
            'name'        => 'League of Legends',
            'slug'        => 'league-of-legends',
            'cover_image' => 'games/lol.jpg',
            'active'      => true,
        ]);

        $dota2 = Game::create([
            'name'        => 'Dota 2',
            'slug'        => 'dota-2',
            'cover_image' => 'games/dota2.jpg',
            'active'      => true,
        ]);

        // 3. Categorias
        $catSkins = Category::create([
            'game_id' => $cs2->id,
            'name'    => 'Skins de Rifles',
            'slug'    => 'skins-rifles',
            'type'    => 'cosmetic',
        ]);

        $catFacas = Category::create([
            'game_id' => $cs2->id,
            'name'    => 'Facas & Luvas',
            'slug'    => 'facas-e-luvas',
            'type'    => 'cosmetic',
        ]);

        $catCoachingVal = Category::create([
            'game_id' => $valorant->id,
            'name'    => 'Sessão de Coaching Competitivo',
            'slug'    => 'coaching-valorant',
            'type'    => 'service',
        ]);

        $catCoachingLol = Category::create([
            'game_id' => $lol->id,
            'name'    => 'Mentoria de Rota e Macroplay',
            'slug'    => 'mentoria-lol',
            'type'    => 'service',
        ]);

        // 4. Anúncios de Teste
        $listing1 = Listing::create([
            'seller_id'   => $seller->id,
            'game_id'     => $cs2->id,
            'category_id' => $catFacas->id,
            'title'       => 'Karambit Doppler (Phase 2) - Pouco Usada',
            'slug'        => 'karambit-doppler-phase-2-' . Str::lower(Str::random(6)),
            'description' => 'Faca Karambit com padrão Doppler Phase 2 (Pink Galaxy). Float baixo (0.015). Envio imediato via Steam Trade Offer com garantia TheMerchant.',
            'price'       => 4850.00,
            'status'      => 'publicado',
            'views_count' => 312,
        ]);

        ListingImage::create([
            'listing_id'    => $listing1->id,
            'image_path'    => 'sample/karambit.jpg',
            'is_primary'    => true,
            'display_order' => 0,
        ]);

        $listing2 = Listing::create([
            'seller_id'   => $seller->id,
            'game_id'     => $cs2->id,
            'category_id' => $catSkins->id,
            'title'       => 'AK-47 Imperatriz (Testada em Campo)',
            'slug'        => 'ak-47-imperatriz-ft-' . Str::lower(Str::random(6)),
            'description' => 'AK-47 A Imperatriz com acabamento impecável, adesivos holográficos já aplicados. Pronto para entrega.',
            'price'       => 280.00,
            'status'      => 'publicado',
            'views_count' => 145,
        ]);

        ListingImage::create([
            'listing_id'    => $listing2->id,
            'image_path'    => 'sample/ak47.jpg',
            'is_primary'    => true,
            'display_order' => 0,
        ]);

        $listing3 = Listing::create([
            'seller_id'   => $seller->id,
            'game_id'     => $valorant->id,
            'category_id' => $catCoachingVal->id,
            'title'       => 'Coaching Individual de Valorant - 2 Horas (VOD Review + Mira)',
            'slug'        => 'coaching-valorant-2h-' . Str::lower(Str::random(6)),
            'description' => 'Aula personalizada com ex-Radiante. Análise completa de VOD, posicionamento de mira, movimentação e estratégias de entrada em bomb.',
            'price'       => 99.90,
            'status'      => 'publicado',
            'views_count' => 88,
        ]);

        ListingImage::create([
            'listing_id'    => $listing3->id,
            'image_path'    => 'sample/coaching.jpg',
            'is_primary'    => true,
            'display_order' => 0,
        ]);
    }
}
