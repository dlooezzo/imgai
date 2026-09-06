<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About IMGAI',
                'slug' => 'about',
                'excerpt' => 'Discover the mission, technology, and team behind IMGAI — the next-generation cinematic AI image and video synthesis suite.',
                'content' => <<<HTML
<h2>Empowering Creators with Generative Intelligence</h2>
<p>IMGAI is an advanced generative AI platform engineered to empower digital artists, designers, and creators worldwide. By combining state-of-the-art diffusion architectures with an intuitive studio workflow, we turn ambitious creative ideas into photorealistic visual assets in seconds.</p>

<h3>Core Neural Technologies</h3>
<p>Our infrastructure orchestrates leading generative foundation models:</p>
<ul>
    <li><strong>Wan 2.2 Cinematic Diffusion:</strong> Generates crisp 2-Megapixel photorealistic imagery with accurate spatial lighting, micro-textures, and rich color dynamics.</li>
    <li><strong>Tencent Hunyuan-Video:</strong> Powers our pure text-to-video generation pipeline with temporal coherence, physical realism, and fluid 24 FPS motion dynamics.</li>
    <li><strong>Wan 2.2 Image-to-Video Engine:</strong> Transforms static photographs and 2D illustrations into living cinematic clips up to 81 frames with seamless camera transitions.</li>
</ul>

<h3>Our Mission</h3>
<p>We believe professional AI tools should be fast, transparent, and accessible without cumbersome setup. Our dedicated high-throughput GPU clusters and persistent Cloudflare R2 storage layer ensure your creations are rendered with sub-second queue dispatch and reliable cloud availability.</p>
HTML,
                'status' => 'published',
                'show_in_navigation' => true,
                'navigation_label' => 'About',
                'navigation_order' => 1,
                'meta_title' => 'About IMGAI — Next-Gen Cinematic AI Studio Platform',
                'meta_description' => 'Discover IMGAI, the next-generation generative AI studio uniting Wan 2.2 and Hunyuan models for photorealistic 2MP image and video synthesis.',
            ],
            [
                'title' => 'Pricing & Generation Plans',
                'slug' => 'pricing',
                'excerpt' => 'Transparent, flexible access for creators, independent studios, and enterprise production teams.',
                'content' => <<<HTML
<h2>Transparent Generation Compute</h2>
<p>Choose the computing tier that fits your production volume. All plans feature direct access to our full suite of Text-to-Image, Text-to-Video, and Image-to-Video neural engines.</p>

<div class="pricing-matrix">
    <h3>Standard Creator Access</h3>
    <p>Ideal for independent artists and content creators generating high-resolution concept art, visual mockups, and social media reels.</p>
    <ul>
        <li>Native 2MP Text-to-Image Generation (JPG/WebP)</li>
        <li>Pure Text-to-Video rendering up to 24 FPS</li>
        <li>Image-to-Video with Wan 2.2</li>
        <li>Persistent Cloudflare R2 Storage integration</li>
        <li>Standard GPU queue priority</li>
    </ul>

    <h3>Studio Pro & Enterprise</h3>
    <p>Engineered for high-velocity design agencies, production houses, and game studios requiring prioritized GPU dispatch and custom pipeline parameters.</p>
    <ul>
        <li>Dedicated high-priority GPU rendering queue</li>
        <li>Extended video frame limits (81+ frames)</li>
        <li>Multi-aspect ratio batch processing</li>
        <li>API Market gateway programmatic access</li>
        <li>Priority 24/7 technical support</li>
    </ul>
</div>

<p>Need custom compute allocations or private model fine-tuning? <a href="/contact">Contact our enterprise team</a> for customized deployments.</p>
HTML,
                'status' => 'published',
                'show_in_navigation' => true,
                'navigation_label' => 'Pricing',
                'navigation_order' => 2,
                'meta_title' => 'Pricing & Generation Plans — IMGAI AI Studio Compute',
                'meta_description' => 'Explore flexible compute tiers and generation plans for IMGAI text-to-image, text-to-video, and image-to-video creative AI tools with zero egress.',
            ],
            [
                'title' => 'Frequently Asked Questions',
                'slug' => 'faq',
                'excerpt' => 'Answers to common questions regarding model capabilities, generation formats, R2 storage, and licensing.',
                'content' => <<<HTML
<h2>Frequently Asked Questions</h2>

<h3>What image resolution does IMGAI support?</h3>
<p>Our Text-to-Image pipeline natively renders images up to 2 Megapixels across multiple aspect ratios including 1:1 (Square), 16:9 (Landscape), 9:16 (Story/Reel), 4:3 (Classic), and 21:9 (Ultrawide).</p>

<h3>Which video models are integrated?</h3>
<p>We utilize <strong>Tencent Hunyuan-Video</strong> for pure Text-to-Video generation and <strong>Wan 2.2</strong> for Image-to-Video transformations. Both output high-definition MP4 files with fluid 24 FPS motion.</p>

<h3>Where are my generated videos stored?</h3>
<p>All video outputs and source upload images are stored securely in <strong>Cloudflare R2 Object Storage</strong>, enabling rapid streaming and zero egress fees when downloading your media assets.</p>

<h3>Do I own the commercial rights to my generated assets?</h3>
<p>Yes. You retain full commercial ownership of all visual media synthesized through your account, subject to standard acceptable use policies.</p>

<h3>How does authentication work?</h3>
<p>We use <strong>Supabase Auth</strong> for secure authentication with support for Email/Password and Google OAuth. Your account identity is synchronized seamlessly across your sessions.</p>
HTML,
                'status' => 'published',
                'show_in_navigation' => true,
                'navigation_label' => 'FAQ',
                'navigation_order' => 3,
                'meta_title' => 'FAQ — Frequently Asked Questions | IMGAI AI Studio',
                'meta_description' => 'Find answers to common questions about IMGAI AI image generator, video generator, resolution formats, Cloudflare R2 storage, and commercial rights.',
            ],
            [
                'title' => 'Contact & Support',
                'slug' => 'contact',
                'excerpt' => 'Get in touch with the IMGAI engineering team for support, partnerships, or API integrations.',
                'content' => <<<HTML
<h2>Get in Touch with Our Team</h2>
<p>Have questions about our generative tools, enterprise integrations, or custom GPU cluster deployments? We're here to help.</p>

<h3>Technical Support</h3>
<p>For assistance with your account, generation queue, or cloud storage, reach out to our customer engineering team at <a href="mailto:support@imgai.studio">support@imgai.studio</a>.</p>

<h3>API & Enterprise Inquiries</h3>
<p>Interested in integrating our cinematic AI pipelines directly into your software workflow via the API Market gateway? Contact our developer solutions team at <a href="mailto:enterprise@imgai.studio">enterprise@imgai.studio</a>.</p>

<h3>Community & Updates</h3>
<p>Follow our product releases and join other digital creators in our community channels for weekly prompt showcases, workflow tutorials, and feature announcements.</p>
HTML,
                'status' => 'published',
                'show_in_navigation' => true,
                'navigation_label' => 'Contact',
                'navigation_order' => 4,
                'meta_title' => 'Contact & Support — IMGAI AI Creative Studio Team',
                'meta_description' => 'Get in touch with the IMGAI engineering and support team for technical assistance, enterprise GPU compute allocations, and API partnerships.',
            ],
            [
                'title' => 'Terms of Service',
                'slug' => 'terms',
                'excerpt' => 'Review the terms, conditions, and acceptable use guidelines governing the IMGAI AI Studio platform.',
                'content' => <<<HTML
<h2>Terms of Service</h2>
<p>Last updated: September 2026</p>

<h3>1. Acceptance of Terms</h3>
<p>By accessing and using the IMGAI AI Studio platform, you agree to comply with and be bound by these Terms of Service. If you do not agree to these terms, please do not use our services.</p>

<h3>2. Permitted Use & Content Guidelines</h3>
<p>You agree not to use IMGAI to generate unlawful, defamatory, infringing, or harmful content. We maintain zero tolerance for non-consensual imagery, hate speech, or content that violates applicable laws.</p>

<h3>3. Intellectual Property Rights</h3>
<p>You retain ownership of the prompts and source images you submit, as well as the output media generated by our AI models through your account, subject to standard cloud terms.</p>

<h3>4. Service Availability & SLA</h3>
<p>While we strive for 99.9% uptime, access to neural generation clusters may occasionally be subject to maintenance or provider rate limits. We reserve the right to modify or enhance platform features at any time.</p>
HTML,
                'status' => 'published',
                'show_in_navigation' => false,
                'navigation_label' => 'Terms',
                'navigation_order' => 5,
                'meta_title' => 'Terms of Service & Usage Guidelines — IMGAI Studio',
                'meta_description' => 'Review the terms of service, acceptable use policies, intellectual property rights, and generation compute guidelines for the IMGAI AI platform.',
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy',
                'excerpt' => 'Learn how IMGAI protects your data, handles Supabase authentication, and safeguards your creative prompts.',
                'content' => <<<HTML
<h2>Privacy Policy</h2>
<p>Last updated: September 2026</p>

<h3>1. Information We Collect</h3>
<p>We collect information necessary to provide and improve our services, including account information (name, email) provided through Supabase Auth, prompt parameters, and generated output files.</p>

<h3>2. Use of Generated Content</h3>
<p>Your creative prompts and generated image/video files are private to your account. We do not sell your personal data or use your private generations to train third-party public foundation models without your explicit consent.</p>

<h3>3. Data Storage & Security</h3>
<p>Authentication sessions are managed via Supabase with industry-standard encryption. Video and image assets are stored in enterprise-grade Cloudflare R2 object storage with granular access controls.</p>

<h3>4. Your Data Rights</h3>
<p>You may request the deletion of your account and associated generation history at any time through your Profile dashboard or by contacting support.</p>
HTML,
                'status' => 'published',
                'show_in_navigation' => false,
                'navigation_label' => 'Privacy',
                'navigation_order' => 6,
                'meta_title' => 'Privacy Policy & Data Security — IMGAI AI Studio',
                'meta_description' => 'Learn how IMGAI protects your creative privacy, secures authentication through Supabase, and stores synthesized assets in Cloudflare R2 storage.',
            ],
        ];

        foreach ($pages as $pageData) {
            Page::updateOrCreate(
                ['slug' => $pageData['slug']],
                $pageData
            );
        }
    }
}
