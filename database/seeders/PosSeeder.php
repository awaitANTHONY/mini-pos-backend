<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ingredient;
use App\Models\Stock;
use App\Models\Category;
use App\Models\Item;
use App\Models\ItemVariant;

class PosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create Categories
        $jhalmuri = Category::create(['title' => 'Jhalmuri', 'status' => 1]);
        $momo = Category::create(['title' => 'Momo', 'status' => 1]);
        $wings = Category::create(['title' => 'Wings', 'status' => 1]);
        $meatbox = Category::create(['title' => 'Meat Box', 'status' => 1]);
        $pasta = Category::create(['title' => 'Pasta', 'status' => 1]);
        $sandwich = Category::create(['title' => 'Sandwich', 'status' => 1]);

        // Create Ingredients (only for Momo and Wings)
        $rawMomo = Ingredient::create(['name' => 'Raw Momo', 'unit' => 'pcs']);
        $rawWings = Ingredient::create(['name' => 'Raw Wings', 'unit' => 'pcs']);

        // Add stock for ingredients
        Stock::create(['ingredient_id' => $rawMomo->id, 'quantity' => 100]);
        Stock::create(['ingredient_id' => $rawWings->id, 'quantity' => 100]);

        // ========== JHALMURI ITEMS (No Ingredients) ==========
        Item::create([
            'category_id' => $jhalmuri->id,
            'name' => 'Chanachur Mix',
            'had_variants' => 0,
            'price' => 30,
            'cost' => 15,
            'status' => 1,
        ]);

        Item::create([
            'category_id' => $jhalmuri->id,
            'name' => 'Egg Blast',
            'had_variants' => 0,
            'price' => 50,
            'cost' => 25,
            'status' => 1,
        ]);

        Item::create([
            'category_id' => $jhalmuri->id,
            'name' => 'Chicken Masala Mix',
            'had_variants' => 0,
            'price' => 70,
            'cost' => 35,
            'status' => 1,
        ]);

        Item::create([
            'category_id' => $jhalmuri->id,
            'name' => '51 Special',
            'had_variants' => 0,
            'price' => 100,
            'cost' => 50,
            'status' => 1,
        ]);

        // ========== MOMO ITEMS (With Ingredients) ==========
        $chickenFriedMomo = Item::create([
            'category_id' => $momo->id,
            'name' => 'Chicken Fried Momo',
            'had_variants' => 1,
            'ingredient_id' => $rawMomo->id,
            'status' => 1,
        ]);

        ItemVariant::create([
            'item_id' => $chickenFriedMomo->id,
            'name' => 'Small (4pcs)',
            'price' => 140,
            'cost' => 70,
            'ingredient_quantity' => 4,
        ]);

        ItemVariant::create([
            'item_id' => $chickenFriedMomo->id,
            'name' => 'Large (8pcs)',
            'price' => 200,
            'cost' => 100,
            'ingredient_quantity' => 8,
        ]);

        $bbqChickenMomo = Item::create([
            'category_id' => $momo->id,
            'name' => 'BBQ Chicken Momo',
            'had_variants' => 1,
            'ingredient_id' => $rawMomo->id,
            'status' => 1,
        ]);

        ItemVariant::create([
            'item_id' => $bbqChickenMomo->id,
            'name' => 'Small (4pcs)',
            'price' => 160,
            'cost' => 80,
            'ingredient_quantity' => 4,
        ]);

        ItemVariant::create([
            'item_id' => $bbqChickenMomo->id,
            'name' => 'Large (8pcs)',
            'price' => 200,
            'cost' => 100,
            'ingredient_quantity' => 8,
        ]);

        $bbqChickenFriedMomo = Item::create([
            'category_id' => $momo->id,
            'name' => 'BBQ Chicken Fried Momo',
            'had_variants' => 1,
            'ingredient_id' => $rawMomo->id,
            'status' => 1,
        ]);

        ItemVariant::create([
            'item_id' => $bbqChickenFriedMomo->id,
            'name' => 'Small (6pcs)',
            'price' => 160,
            'cost' => 80,
            'ingredient_quantity' => 6,
        ]);

        ItemVariant::create([
            'item_id' => $bbqChickenFriedMomo->id,
            'name' => 'Large (8pcs)',
            'price' => 200,
            'cost' => 100,
            'ingredient_quantity' => 8,
        ]);

        $specialMomo = Item::create([
            'category_id' => $momo->id,
            'name' => '51 Special Momo',
            'had_variants' => 1,
            'ingredient_id' => $rawMomo->id,
            'status' => 1,
        ]);

        ItemVariant::create([
            'item_id' => $specialMomo->id,
            'name' => 'Small (8pcs)',
            'price' => 220,
            'cost' => 110,
            'ingredient_quantity' => 8,
        ]);

        // ========== WINGS ITEMS (With Ingredients) ==========
        $nagaWings = Item::create([
            'category_id' => $wings->id,
            'name' => 'NAGA Wings',
            'had_variants' => 1,
            'ingredient_id' => $rawWings->id,
            'status' => 1,
        ]);

        ItemVariant::create([
            'item_id' => $nagaWings->id,
            'name' => 'Small (4pcs)',
            'price' => 150,
            'cost' => 75,
            'ingredient_quantity' => 4,
        ]);

        ItemVariant::create([
            'item_id' => $nagaWings->id,
            'name' => 'Large (8pcs)',
            'price' => 200,
            'cost' => 100,
            'ingredient_quantity' => 8,
        ]);

        $bbqWings = Item::create([
            'category_id' => $wings->id,
            'name' => 'BBQ Wings',
            'had_variants' => 1,
            'ingredient_id' => $rawWings->id,
            'status' => 1,
        ]);

        ItemVariant::create([
            'item_id' => $bbqWings->id,
            'name' => 'Small (4pcs)',
            'price' => 170,
            'cost' => 85,
            'ingredient_quantity' => 4,
        ]);

        ItemVariant::create([
            'item_id' => $bbqWings->id,
            'name' => 'Large (8pcs)',
            'price' => 220,
            'cost' => 110,
            'ingredient_quantity' => 8,
        ]);

        $sweetChilliWings = Item::create([
            'category_id' => $wings->id,
            'name' => 'Sweet Chilli Wings',
            'had_variants' => 1,
            'ingredient_id' => $rawWings->id,
            'status' => 1,
        ]);

        ItemVariant::create([
            'item_id' => $sweetChilliWings->id,
            'name' => 'Small (4pcs)',
            'price' => 170,
            'cost' => 85,
            'ingredient_quantity' => 4,
        ]);

        ItemVariant::create([
            'item_id' => $sweetChilliWings->id,
            'name' => 'Large (8pcs)',
            'price' => 220,
            'cost' => 110,
            'ingredient_quantity' => 8,
        ]);

        // ========== MEAT BOX ITEMS (No Ingredients) ==========
        $bbqMeatBox = Item::create([
            'category_id' => $meatbox->id,
            'name' => 'BBQ MeatBox',
            'had_variants' => 1,
            'status' => 1,
        ]);

        ItemVariant::create([
            'item_id' => $bbqMeatBox->id,
            'name' => 'Small',
            'price' => 130,
            'cost' => 65,
        ]);

        ItemVariant::create([
            'item_id' => $bbqMeatBox->id,
            'name' => 'Large',
            'price' => 160,
            'cost' => 80,
        ]);

        $cheesyMeatBox = Item::create([
            'category_id' => $meatbox->id,
            'name' => 'Cheesy MeatBox',
            'had_variants' => 1,
            'status' => 1,
        ]);

        ItemVariant::create([
            'item_id' => $cheesyMeatBox->id,
            'name' => 'Small',
            'price' => 150,
            'cost' => 75,
        ]);

        ItemVariant::create([
            'item_id' => $cheesyMeatBox->id,
            'name' => 'Large',
            'price' => 180,
            'cost' => 90,
        ]);

        Item::create([
            'category_id' => $meatbox->id,
            'name' => '51 Special MeatBox',
            'had_variants' => 0,
            'price' => 200,
            'cost' => 100,
            'status' => 1,
        ]);

        // ========== PASTA ITEMS (No Ingredients) ==========
        Item::create([
            'category_id' => $pasta->id,
            'name' => 'Spicy Chicken Pasta',
            'had_variants' => 0,
            'price' => 150,
            'cost' => 75,
            'status' => 1,
        ]);

        Item::create([
            'category_id' => $pasta->id,
            'name' => '51 Special Pasta',
            'had_variants' => 0,
            'price' => 200,
            'cost' => 100,
            'status' => 1,
        ]);

        // ========== SANDWICH ITEMS (No Ingredients) ==========
        Item::create([
            'category_id' => $sandwich->id,
            'name' => 'Classic Sandwich',
            'had_variants' => 0,
            'price' => 120,
            'cost' => 60,
            'status' => 1,
        ]);

        Item::create([
            'category_id' => $sandwich->id,
            'name' => '51 Special Sandwich',
            'had_variants' => 0,
            'price' => 150,
            'cost' => 75,
            'status' => 1,
        ]);

        $this->command->info('Spice 51 menu seeded successfully!');
    }
}
