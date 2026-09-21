<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\SellerProfile;
use App\Models\Game;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuários de Demonstração
        User::create([
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

        User::create([
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

        Game::create([
            'name'        => 'Dota 2',
            'slug'        => 'dota-2',
            'cover_image' => 'games/dota2.jpg',
            'active'      => true,
        ]);

        // 3. Categorias
        Category::create([
            'game_id' => $cs2->id,
            'name'    => 'Skins de Rifles',
            'slug'    => 'skins-rifles',
            'type'    => 'cosmetic',
        ]);

        Category::create([
            'game_id' => $cs2->id,
            'name'    => 'Facas & Luvas',
            'slug'    => 'facas-e-luvas',
            'type'    => 'cosmetic',
        ]);

        Category::create([
            'game_id' => $valorant->id,
            'name'    => 'Sessão de Coaching Competitivo',
            'slug'    => 'coaching-valorant',
            'type'    => 'service',
        ]);

        Category::create([
            'game_id' => $lol->id,
            'name'    => 'Mentoria de Rota e Macroplay',
            'slug'    => 'mentoria-lol',
            'type'    => 'service',
        ]);

        // Anúncios são cadastrados manualmente para os testes do marketplace.
    }
}
