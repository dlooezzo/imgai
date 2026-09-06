<?php

namespace Database\Seeders;

use App\Models\ToolArticle;
use Illuminate\Database\Seeder;

class ToolArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'tool_key' => 'image-generator',
                'title' => 'The Complete Guide to 2MP Photorealistic AI Image Generation with Wan 2.2',
                'slug' => 'guide-to-2mp-photorealistic-ai-image-generation-wan-2-2',
                'seo_title' => 'Text-to-Image AI Generator — Wan 2.2 Cinematic 2MP | IMGAI',
                'meta_description' => 'Synthesize photorealistic 2MP ultra-high definition images from text prompts with precise lighting, rich texture, and custom aspect ratios on IMGAI.',
                'excerpt' => 'Discover how Wan 2.2 neural diffusion empowers digital creators to synthesize 2-Megapixel cinematic imagery with pinpoint lighting, physical textures, and zero prompt hallucination.',
                'content_html' => <<<HTML
<h2>What is Wan 2.2 Text-to-Image Neural Diffusion?</h2>
<p>The <strong>Wan 2.2 Cinematic Diffusion Model</strong> represents a paradigm leap in generative visual synthesis. Unlike legacy text-to-image models that suffer from texture smearing, anatomically distorted hands, and resolution upscaling artifacts, Wan 2.2 renders native <strong>2-Megapixel (2MP) ultra-high-definition</strong> frames directly during latent sampling.</p>

<figure class="article-figure">
    <img src="https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?auto=format&fit=crop&w=1200&q=80" alt="Cinematic abstract lighting preview in Wan 2.2 neural diffusion" loading="lazy" class="article-img">
    <figcaption>Native 2MP latent rendering preserves micro-contrast and realistic subsurface scattering.</figcaption>
</figure>

<h3>Key Architectural Advantages</h3>
<ul>
    <li><strong>Native 2MP Spatial Resolution:</strong> Produces crisp 1920x1080, 2048x2048, and 21:9 ultrawide frames without post-generation interpolation blur.</li>
    <li><strong>Photometric Lighting & Shadows:</strong> Simulates volumetric god rays, physically accurate reflections, and real optical lens bokeh (35mm, 50mm, 85mm anamorphic).</li>
    <li><strong>Multi-Aspect Ratio Flexibility:</strong> Effortlessly switch between 1:1 (Square), 16:9 (Landscape), 9:16 (Story/TikTok), 4:3 (Classic), and 21:9 (Cinematic Film).</li>
    <li><strong>Sub-Second GPU Dispatch:</strong> Powered by dedicated high-throughput compute clusters with instant Cloudflare R2 storage persistence.</li>
</ul>

<h2>How to Write High-Impact Prompts for Wan 2.2</h2>
<p>To maximize output fidelity, structure your text prompts using the <em>Subject &bull; Setting &bull; Lighting &bull; Camera Optics</em> methodology:</p>

<pre><code>A cinematic photorealistic portrait of an astronaut on a desert planet at sunset, 35mm anamorphic lens, golden hour volumetric lighting, intricate spacesuit textures, highly detailed, 8k --ar 16:9</code></pre>

<blockquote>
    <p>"Wan 2.2 responds with surgical precision to photographic lens specifications, film stock descriptions, and nuanced directional lighting cues."</p>
</blockquote>

<h3>Recommended Composition Guidelines</h3>
<ol>
    <li><strong>Define the Subject Clearly:</strong> Specify materials, age, clothing textures, and expressions.</li>
    <li><strong>Establish Atmospheric Mood:</strong> Include atmospheric terms like <em>morning fog, volumetric dusk, neon cyber haze</em>.</li>
    <li><strong>Specify Optical Characteristics:</strong> Use <em>bokeh, shallow depth of field, f/1.8 aperture, 35mm film grain</em>.</li>
</ol>

<h2>Frequently Asked Questions (FAQ)</h2>
<h3>Do I retain commercial rights to generated images?</h3>
<p>Yes. All images synthesized through IMGAI are 100% commercially owned by you, suitable for client work, print media, social content, and game design.</p>

<h3>What image formats are supported for export?</h3>
<p>You can instantly download lossless high-resolution JPG and WebP files directly to your device or save them in your private Cloudflare R2 cloud library.</p>
HTML,
                'status' => 'published',
                'published_at' => now(),
            ],
            [
                'tool_key' => 'video-generator',
                'title' => 'Mastering Cinematic Text-to-Video Synthesis with Tencent Hunyuan Diffusion at 24 FPS',
                'slug' => 'mastering-cinematic-text-to-video-tencent-hunyuan-24fps',
                'seo_title' => 'Text-to-Video AI Generator — Hunyuan Diffusion 24FPS | IMGAI',
                'meta_description' => 'Generate fluid temporal cinematic motion video sequences from text prompts at 24 FPS with direct high-definition MP4 export on IMGAI AI Studio.',
                'excerpt' => 'Explore how Tencent Hunyuan-Video enables digital filmmakers to produce temporal fluid 24 FPS video sequences with physical realism and camera path controls.',
                'content_html' => <<<HTML
<h2>The Era of Pure Text-to-Video Generative Cinema</h2>
<p>Text-to-video synthesis is transforming visual storytelling. Powered by <strong>Tencent Hunyuan-Video</strong>, IMGAI allows artists, agencies, and filmmakers to generate smooth, physically coherent video clips up to 24 frames per second from simple descriptive sentences.</p>

<figure class="article-figure">
    <img src="https://images.unsplash.com/photo-1536240478700-b869070f9279?auto=format&fit=crop&w=1200&q=80" alt="Cinematic temporal video generation preview at 24 FPS" loading="lazy" class="article-img">
    <figcaption>Fluid temporal consistency prevents object warping across continuous frame sequences.</figcaption>
</figure>

<h3>Understanding Temporal Diffusion Architecture</h3>
<p>Unlike simple image frame interpolators, Tencent Hunyuan-Video uses a <strong>3D Spatio-Temporal Transformer (DiT)</strong> backbone. This architecture understands gravity, fluid momentum, fabric drape, and camera trajectory over time.</p>

<ul>
    <li><strong>Temporal Coherence:</strong> Characters, landscapes, and moving objects maintain visual consistency from frame 1 to frame 81+.</li>
    <li><strong>Camera Motion Synthetics:</strong> Support for pan, tilt, zoom, drone time-lapses, and handheld cinematic camera motion.</li>
    <li><strong>Direct MP4 Video Export:</strong> Encoded in universal H.264 MP4 with optimal bitrates for instant social reels and editorial timelines.</li>
</ul>

<h2>Proven Prompt Recipes for AI Video Generation</h2>
<p>When generating video, describe both the <em>static scene</em> and the <em>temporal action</em> taking place:</p>

<pre><code>A slow cinematic drone shot ascending through misty emerald pine valleys at dawn, golden sunbeams piercing through pine needles, gentle breeze moving tree branches, smooth 24 fps cinematic motion, 4k resolution</code></pre>

<blockquote>
    <p>"Always describe the camera vector (e.g., 'dolly in', 'smooth tracking shot', 'static tripod view') to control motion dynamics."</p>
</blockquote>

<h3>Best Practices for Video Creators</h3>
<ol>
    <li><strong>Focus on a Single Core Action:</strong> Describe one clear movement (e.g., <em>water flowing over stones, character turning toward camera</em>).</li>
    <li><strong>Avoid Overloading Actions:</strong> Complex sequences with five simultaneous actions may dilute temporal focus.</li>
    <li><strong>Specify Frame Rates:</strong> Mention <em>24 FPS cinematic pacing</em> or <em>slow-motion capture</em> for desired rhythm.</li>
</ol>
HTML,
                'status' => 'published',
                'published_at' => now(),
            ],
            [
                'tool_key' => 'image-to-video',
                'title' => 'How to Animate Static Artwork into 24 FPS Living Motion Sequences with Wan 2.2 & Cloudflare R2',
                'slug' => 'how-to-animate-static-artwork-into-video-wan-2-2-r2',
                'seo_title' => 'Image-to-Video AI Generator — Wan 2.2 Motion Studio | IMGAI',
                'meta_description' => 'Transform static pictures into living cinematic motion clips with camera dynamic controls and high-speed Cloudflare R2 cloud storage on IMGAI.',
                'excerpt' => 'Learn how to transform concept art, photography, and illustrations into broadcast-ready motion sequences using Wan 2.2 image-conditioned diffusion.',
                'content_html' => <<<HTML
<h2>Transforming 2D Artwork into Dynamic Cinematic Motion</h2>
<p>The <strong>Wan 2.2 Image-to-Video (I2V)</strong> pipeline bridges the gap between static concept art and motion picture production. By taking any source image—whether a photograph, 3D render, or AI illustration—Wan 2.2 extrapolates temporal physics while strictly maintaining the original character identity and visual composition.</p>

<figure class="article-figure">
    <img src="https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=1200&q=80" alt="Image to video motion synthesis and lighting preview" loading="lazy" class="article-img">
    <figcaption>Wan 2.2 preserves facial symmetry, clothing detail, and background architecture during motion.</figcaption>
</figure>

<h3>How the Image-Conditioned Pipeline Works</h3>
<ol>
    <li><strong>Source Image Ingestion:</strong> Upload your source JPEG, PNG, or WebP file. It is instantly dispatched to <strong>Cloudflare R2 Object Storage</strong> for low-latency retrieval.</li>
    <li><strong>Motion Prompt Conditioning:</strong> Enter a motion prompt describing how the elements in the picture should move (e.g., <em>hair blowing in wind, gentle camera orbit, waves rippling</em>).</li>
    <li><strong>Latent Temporal Synthesis:</strong> Wan 2.2 generates 81 frames of continuous motion preserving your original art's exact style.</li>
    <li><strong>Zero-Egress Export:</strong> Stream or download the completed high-definition MP4 directly from Cloudflare R2.</li>
</ol>

<h2>Top Use Cases for Image-to-Video</h2>
<ul>
    <li><strong>Product Advertising:</strong> Animate static product photos into 360-degree floating showcase videos.</li>
    <li><strong>Game Concept Pitches:</strong> Turn static environment sketches into living atmospheric flythroughs.</li>
    <li><strong>Social Media Reels:</strong> Breathe life into portrait photography for eye-catching Instagram and TikTok content.</li>
    <li><strong>NFT & Digital Art:</strong> Convert 2D collector pieces into animated museum-grade displays.</li>
</ul>

<blockquote>
    <p>"Image-to-Video unlocks infinite creative reuse for existing asset libraries, turning dormant artwork into viral motion content."</p>
</blockquote>
HTML,
                'status' => 'published',
                'published_at' => now(),
            ],
        ];

        foreach ($articles as $data) {
            ToolArticle::updateOrCreate(
                ['tool_key' => $data['tool_key']],
                $data
            );
        }
    }
}
