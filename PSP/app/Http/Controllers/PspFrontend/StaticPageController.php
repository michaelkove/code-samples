<?php

namespace App\Http\Controllers\PspFrontend;

use App\Helpers\SiteHelper;
use App\Models\EmailInvite;
use App\Models\Pool as Pool;
use App\Models\StaticPage;
use App\Providers\Pool\Pool as PoolProvider;
use App\Models\Game;
use App\Models\SquareBoard;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\UpdateUserPasswordRequest as UpdateRequest;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;


class StaticPageController extends Controller
{

    public $crumbs;

    public  function  __construct()
    {
        $this->help_content = "";
        $this->help_title = "";
    }

    public function show(StaticPage $staticPage)
    {

        //            $staticPage = StaticPage::where('slug', $slug)->where('active', true)->orderBy('order','asc')->first();
        if ($staticPage) {
            $slug = $staticPage->slug;
            $meta = [
                'title' => __c('website.seo.page-' . $slug . '.title', ''),
                'description' => __c('website.seo.page-' . $slug . '.description', ''),
                'og_title' => __c('website.seo.page-' . $slug . '.og-title', ''),
                'og_description' => __c('website.seo.page-' . $slug . '.og-description', ''),
                'keywords' => __c('website.seo.page-' . $slug . '.keywords', ''),
                'robots' => __c('website.seo.page-' . $slug . '.robots', "index,follow")
            ];

            SiteHelper::set_help($this->help_title, $this->help_content, 'general', 'static-page');
            return view('pspfrontend.pages.page', compact('staticPage', 'meta'));
        }
        abort(404);
    }

    public function privacy()
    {

        \Site::build_crumbs($this->crumbs, [
            'link' => "/",
            'label' => "Home",
        ], [
            'link' => "/privacy-policy",
            'label' => "Privacy Policy",
            'active' => true,
            'help_title' => $this->help_title,
            'help_content' => $this->help_content
        ]);
        SiteHelper::set_help($this->help_title, $this->help_content, 'privacy', 'static-page');
        return view(
            'pspfrontend.pages.privacy',
            [
                'crumbs' => $this->crumbs,
                'help_title' => $this->help_title,
                'help_content' => $this->help_content,
            ]
        );
    }

    public function terms()
    {
        \Site::build_crumbs($this->crumbs, [
            'link' => "/",
            'label' => "Home",
        ], [
            'link' => "/terms-and-conditions",
            'label' => "Terms & Conditions",
            'active' => true,
        ]);
        SiteHelper::set_help($this->help_title, $this->help_content, 'terms', 'static-page');
        return view(
            'pspfrontend.pages.terms',
            [
                'crumbs' => $this->crumbs,
                'help_title' => $this->help_title,
                'help_content' => $this->help_content,
            ]
        );
    }

    public function how_it_works()
    {
        \Site::build_crumbs($this->crumbs, [
            'link' => "/",
            'label' => "Home",
        ], [
            'link' => "/how-it-works",
            'label' => "How It Works",
            'active' => true,
        ]);
        SiteHelper::set_help($this->help_title, $this->help_content, 'how-it-works', 'static-page');
        return view(
            'pspfrontend.pages.how-it-works',
            [
                'crumbs' => $this->crumbs,
                'help_title' => $this->help_title,
                'help_content' => $this->help_content,
            ]
        );
    }

    public function contact()
    {
        \Site::build_crumbs($this->crumbs, [
            'link' => "/",
            'label' => "Home",
        ], [
            'link' => "/contact",
            'label' => "Contact",
            'active' => true,
        ]);
        SiteHelper::set_help($this->help_title, $this->help_content, 'contact', 'static-page');
        return view(
            'pspfrontend.pages.contact',
            [
                'crumbs' => $this->crumbs,
                'help_title' => $this->help_title,
                'help_content' => $this->help_content,
            ]
        );
    }

    public function about()
    {
        \Site::build_crumbs($this->crumbs, [
            'link' => "/",
            'label' => "Home",
        ], [
            'link' => "/about",
            'label' => "About",
            'active' => true,
        ]);
        SiteHelper::set_help($this->help_title, $this->help_content, 'about', 'static-page');
        return view(
            'pspfrontend.pages.about',
            [
                'crumbs' => $this->crumbs,
                'help_title' => $this->help_title,
                'help_content' => $this->help_content,
            ]
        );
    }
}
