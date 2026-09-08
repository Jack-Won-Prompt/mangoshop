<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeCategory;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [];
        $urls[] = ['loc' => url('/'), 'freq' => 'daily', 'pri' => '1.0'];
        $urls[] = ['loc' => route('catalog.index'), 'freq' => 'daily', 'pri' => '0.9'];
        $urls[] = ['loc' => route('community.recipes'), 'freq' => 'daily', 'pri' => '0.9'];

        // 상품
        Product::where('is_active', true)->select('slug', 'updated_at')->orderByDesc('id')->limit(2000)
            ->get()->each(function ($p) use (&$urls) {
                if ($p->slug) {
                    $urls[] = ['loc' => route('catalog.show', $p->slug), 'freq' => 'weekly', 'pri' => '0.7', 'mod' => $p->updated_at];
                }
            });

        // 레시피 카테고리 + 글
        RecipeCategory::where('is_active', true)->get()->each(function ($c) use (&$urls) {
            $urls[] = ['loc' => route('community.recipe.category', $c->slug), 'freq' => 'weekly', 'pri' => '0.7'];
        });
        Recipe::published()->with('category')->select('id', 'recipe_category_id', 'slug', 'updated_at')->limit(3000)
            ->get()->each(function ($r) use (&$urls) {
                if ($r->category) {
                    $urls[] = ['loc' => route('community.recipe.show', [$r->category->slug, $r->slug]), 'freq' => 'weekly', 'pri' => '0.8', 'mod' => $r->updated_at];
                }
            });

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>'.e($u['loc']).'</loc>';
            if (! empty($u['mod'])) {
                $xml .= '<lastmod>'.$u['mod']->toAtomString().'</lastmod>';
            }
            $xml .= '<changefreq>'.$u['freq'].'</changefreq><priority>'.$u['pri'].'</priority></url>'."\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
