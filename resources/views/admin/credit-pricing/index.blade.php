@extends('admin.layouts.app')

@section('title', 'AI Credit Pricing')
@section('breadcrumb', 'Billing & Pricing / AI Credit Pricing')

@section('content')
<div x-data="aiPricingManager()" style="display: flex; flex-direction: column; gap: 28px;">

    <!-- Top Action Header -->
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.02em; margin-bottom: 4px; color: #0f172a;">
                AI Credit Pricing Management
            </h1>
            <p style="color: #64748b; font-size: 0.88rem;">
                Configure centralized credit costs and multipliers for all AI generators. Database is the authoritative source of truth.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <button type="submit" form="ai-pricing-form" class="btn-admin btn-admin-primary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700; padding: 10px 20px;">
                <i data-lucide="save" style="width: 16px; height: 16px;"></i>
                <span>Save Credit Pricing</span>
            </button>
        </div>
    </div>

    <!-- Pricing Configuration Form -->
    <form id="ai-pricing-form" method="POST" action="{{ route('admin.credit-pricing.update') }}" style="display: flex; flex-direction: column; gap: 24px;">
        @csrf

        <!-- Section 1: Fixed Credit Costs -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h2 class="admin-card-title" style="font-size: 1.15rem; font-weight: 700; color: #0f172a;">Fixed Credit Costs</h2>
                        <span class="badge" style="background: rgba(99, 102, 241, 0.12); color: #4f46e5; border: 1px solid rgba(99, 102, 241, 0.25); font-weight: 600; padding: 3px 10px; border-radius: 9999px; font-size: 0.75rem;">
                            Base Unit Costs
                        </span>
                    </div>
                    <p class="admin-card-subtitle" style="color: #64748b; font-size: 0.82rem; margin-top: 4px;">
                        Set fixed credit amounts charged per generation or used as base multiplier anchor.
                    </p>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 20px;">
                <!-- Image Generation -->
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; color: #334155; font-size: 0.88rem; display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                        <i data-lucide="image" style="width: 16px; height: 16px; color: #6366f1;"></i>
                        <span>Text-to-Image (Wan 2.2)</span>
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="number" 
                            name="image_generation" 
                            class="form-input" 
                            style="width: 100%; padding: 10px 14px; font-size: 0.95rem; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1;" 
                            min="1" 
                            max="1000" 
                            value="{{ old('image_generation', $settings['image_generation']) }}" 
                            required
                        >
                    </div>
                    <span style="font-size: 0.76rem; color: #94a3b8; margin-top: 4px; display: block;">Credits deducted per image generation (Default: 1)</span>
                    @error('image_generation')
                        <span style="color: #dc2626; font-size: 0.78rem; font-weight: 600;">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Image-to-Video -->
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; color: #334155; font-size: 0.88rem; display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                        <i data-lucide="clapperboard" style="width: 16px; height: 16px; color: #06b6d4;"></i>
                        <span>Image-to-Video (Wan 2.2)</span>
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="number" 
                            name="image_to_video" 
                            class="form-input" 
                            style="width: 100%; padding: 10px 14px; font-size: 0.95rem; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1;" 
                            min="1" 
                            max="1000" 
                            value="{{ old('image_to_video', $settings['image_to_video']) }}" 
                            required
                        >
                    </div>
                    <span style="font-size: 0.76rem; color: #94a3b8; margin-top: 4px; display: block;">Credits deducted per image-to-video clip (Default: 5)</span>
                    @error('image_to_video')
                        <span style="color: #dc2626; font-size: 0.78rem; font-weight: 600;">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Text-to-Video Base -->
                <div class="form-group">
                    <label class="form-label" style="font-weight: 600; color: #334155; font-size: 0.88rem; display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                        <i data-lucide="video" style="width: 16px; height: 16px; color: #a855f7;"></i>
                        <span>Text-to-Video Base Cost</span>
                    </label>
                    <div style="position: relative;">
                        <input 
                            type="number" 
                            name="video_base" 
                            class="form-input" 
                            style="width: 100%; padding: 10px 14px; font-size: 0.95rem; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1;" 
                            min="1" 
                            max="1000" 
                            x-model.number="form.video_base"
                            value="{{ old('video_base', $settings['video_base']) }}" 
                            required
                        >
                    </div>
                    <span style="font-size: 0.76rem; color: #94a3b8; margin-top: 4px; display: block;">Base anchor multiplied by Resolution, Duration & Audio (Default: 5)</span>
                    @error('video_base')
                        <span style="color: #dc2626; font-size: 0.78rem; font-weight: 600;">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Section 2: Text-to-Video Multipliers -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h2 class="admin-card-title" style="font-size: 1.15rem; font-weight: 700; color: #0f172a;">Text-to-Video Multipliers (Seedance 1.5 Pro)</h2>
                        <span class="badge" style="background: rgba(168, 85, 247, 0.12); color: #9333ea; border: 1px solid rgba(168, 85, 247, 0.25); font-weight: 600; padding: 3px 10px; border-radius: 9999px; font-size: 0.75rem;">
                            Formula: Base × Res × Dur × Audio
                        </span>
                    </div>
                    <p class="admin-card-subtitle" style="color: #64748b; font-size: 0.82rem; margin-top: 4px;">
                        All multipliers are dynamic and editable. Saving instantly updates the calculation across backend and frontend without restart.
                    </p>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 24px;">
                <!-- Resolution Multipliers -->
                <div>
                    <h3 style="font-size: 0.92rem; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="monitor" style="width: 15px; height: 15px; color: #6366f1;"></i>
                        <span>Resolution Multipliers</span>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">480p Multiplier</label>
                            <input 
                                type="number" 
                                name="video_resolution_480p" 
                                class="form-input" 
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1;" 
                                min="1" 
                                max="50" 
                                x-model.number="form.res_480p"
                                value="{{ old('video_resolution_480p', $settings['video_resolution_480p']) }}" 
                                required
                            >
                            <span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 3px;">Default: 1</span>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">720p (HD) Multiplier</label>
                            <input 
                                type="number" 
                                name="video_resolution_720p" 
                                class="form-input" 
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1;" 
                                min="1" 
                                max="50" 
                                x-model.number="form.res_720p"
                                value="{{ old('video_resolution_720p', $settings['video_resolution_720p']) }}" 
                                required
                            >
                            <span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 3px;">Default: 2</span>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">1080p (FHD) Multiplier</label>
                            <input 
                                type="number" 
                                name="video_resolution_1080p" 
                                class="form-input" 
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1;" 
                                min="1" 
                                max="50" 
                                x-model.number="form.res_1080p"
                                value="{{ old('video_resolution_1080p', $settings['video_resolution_1080p']) }}" 
                                required
                            >
                            <span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 3px;">Default: 3</span>
                        </div>
                    </div>
                </div>

                <!-- Duration Multipliers -->
                <div>
                    <h3 style="font-size: 0.92rem; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="clock" style="width: 15px; height: 15px; color: #06b6d4;"></i>
                        <span>Duration Multipliers</span>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">5 Seconds Multiplier</label>
                            <input 
                                type="number" 
                                name="video_duration_5" 
                                class="form-input" 
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1;" 
                                min="1" 
                                max="50" 
                                x-model.number="form.dur_5"
                                value="{{ old('video_duration_5', $settings['video_duration_5']) }}" 
                                required
                            >
                            <span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 3px;">Default: 1</span>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">8 Seconds Multiplier</label>
                            <input 
                                type="number" 
                                name="video_duration_8" 
                                class="form-input" 
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1;" 
                                min="1" 
                                max="50" 
                                x-model.number="form.dur_8"
                                value="{{ old('video_duration_8', $settings['video_duration_8']) }}" 
                                required
                            >
                            <span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 3px;">Default: 2</span>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">12 Seconds Multiplier</label>
                            <input 
                                type="number" 
                                name="video_duration_12" 
                                class="form-input" 
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1;" 
                                min="1" 
                                max="50" 
                                x-model.number="form.dur_12"
                                value="{{ old('video_duration_12', $settings['video_duration_12']) }}" 
                                required
                            >
                            <span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 3px;">Default: 3</span>
                        </div>
                    </div>
                </div>

                <!-- Audio Multipliers -->
                <div>
                    <h3 style="font-size: 0.92rem; font-weight: 700; color: #1e293b; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i data-lucide="volume-2" style="width: 15px; height: 15px; color: #3b82f6;"></i>
                        <span>Audio Multipliers</span>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">No Audio (Mute) Multiplier</label>
                            <input 
                                type="number" 
                                name="video_audio_no" 
                                class="form-input" 
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1;" 
                                min="1" 
                                max="50" 
                                x-model.number="form.audio_no"
                                value="{{ old('video_audio_no', $settings['video_audio_no']) }}" 
                                required
                            >
                            <span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 3px;">Default: 1</span>
                        </div>

                        <div>
                            <label class="form-label" style="font-size: 0.82rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">With Audio Multiplier</label>
                            <input 
                                type="number" 
                                name="video_audio_yes" 
                                class="form-input" 
                                style="width: 100%; padding: 8px 12px; font-size: 0.9rem; font-weight: 600; border-radius: 6px; border: 1px solid #cbd5e1;" 
                                min="1" 
                                max="50" 
                                x-model.number="form.audio_yes"
                                value="{{ old('video_audio_yes', $settings['video_audio_yes']) }}" 
                                required
                            >
                            <span style="font-size: 0.72rem; color: #94a3b8; display: block; margin-top: 3px;">Default: 2 (Doubles the video credits)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 3: Registration Free Starting Credits -->
        <div class="admin-card">
            <div class="admin-card-header">
                <div>
                    <h2 class="admin-card-title" style="font-size: 1.15rem; font-weight: 700; color: #0f172a;">Registration Starting Credits</h2>
                    <p class="admin-card-subtitle" style="color: #64748b; font-size: 0.82rem; margin-top: 4px;">
                        Number of free credits automatically granted to newly registered users.
                    </p>
                </div>
            </div>

            <div style="max-width: 320px;">
                <label class="form-label" style="font-size: 0.84rem; font-weight: 600; color: #475569; display: block; margin-bottom: 6px;">Default Free Credits</label>
                <input 
                    type="number" 
                    name="default_free_credits" 
                    class="form-input" 
                    style="width: 100%; padding: 10px 14px; font-size: 0.95rem; font-weight: 600; border-radius: 8px; border: 1px solid #cbd5e1;" 
                    min="0" 
                    max="100000" 
                    value="{{ old('default_free_credits', $settings['default_free_credits']) }}" 
                    required
                >
                <span style="font-size: 0.74rem; color: #94a3b8; display: block; margin-top: 4px;">Set to 0 if users must purchase a plan before generating (Default: 0)</span>
            </div>
        </div>
    </form>

    <!-- Section 4: Live Pricing Preview Matrix -->
    <div class="admin-card">
        <div class="admin-card-header" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <h2 class="admin-card-title" style="font-size: 1.15rem; font-weight: 700; color: #0f172a;">
                        Live Pricing Preview Matrix
                    </h2>
                    <span class="badge" style="background: rgba(16, 185, 129, 0.12); color: #059669; border: 1px solid rgba(16, 185, 129, 0.25); font-weight: 600; padding: 3px 10px; border-radius: 9999px; font-size: 0.75rem;">
                        Real-time Calculation
                    </span>
                </div>
                <p class="admin-card-subtitle" style="color: #64748b; font-size: 0.82rem; margin-top: 4px;">
                    This table previews the exact resulting credits for every resolution, duration, and audio combination using your active input values.
                </p>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.88rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; background: #f8fafc;">
                        <th style="padding: 12px 16px; font-weight: 700; color: #475569;">Resolution</th>
                        <th style="padding: 12px 16px; font-weight: 700; color: #475569;">Duration</th>
                        <th style="padding: 12px 16px; font-weight: 700; color: #475569;">Audio</th>
                        <th style="padding: 12px 16px; font-weight: 700; color: #475569;">Formula Breakdown</th>
                        <th style="padding: 12px 16px; font-weight: 700; color: #0f172a; text-align: right;">Total Credits</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="(item, idx) in previewList" :key="idx">
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" :style="idx % 2 === 1 ? 'background: #f8fafc;' : ''">
                            <td style="padding: 12px 16px; font-weight: 600; color: #1e293b;" x-text="item.resolution"></td>
                            <td style="padding: 12px 16px; color: #475569;" x-text="item.duration"></td>
                            <td style="padding: 12px 16px;">
                                <span :style="item.has_audio ? 'background: rgba(56, 189, 248, 0.15); color: #0284c7; border: 1px solid rgba(56, 189, 248, 0.3);' : 'background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0;'" style="padding: 3px 8px; border-radius: 6px; font-size: 0.78rem; font-weight: 700;" x-text="item.audio"></span>
                            </td>
                            <td style="padding: 12px 16px; color: #64748b; font-family: monospace; font-size: 0.84rem;" x-text="item.formula"></td>
                            <td style="padding: 12px 16px; text-align: right;">
                                <span style="font-weight: 800; font-size: 1rem; color: #7c3aed;" x-text="item.total + ' Credits'"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function aiPricingManager() {
        return {
            form: {
                video_base: {{ (int) old('video_base', $settings['video_base']) }},
                res_480p: {{ (int) old('video_resolution_480p', $settings['video_resolution_480p']) }},
                res_720p: {{ (int) old('video_resolution_720p', $settings['video_resolution_720p']) }},
                res_1080p: {{ (int) old('video_resolution_1080p', $settings['video_resolution_1080p']) }},
                dur_5: {{ (int) old('video_duration_5', $settings['video_duration_5']) }},
                dur_8: {{ (int) old('video_duration_8', $settings['video_duration_8']) }},
                dur_12: {{ (int) old('video_duration_12', $settings['video_duration_12']) }},
                audio_no: {{ (int) old('video_audio_no', $settings['video_audio_no']) }},
                audio_yes: {{ (int) old('video_audio_yes', $settings['video_audio_yes']) }},
            },

            get previewList() {
                const base = Math.max(1, Number(this.form.video_base) || 1);
                const resMap = {
                    '480p': Math.max(1, Number(this.form.res_480p) || 1),
                    '720p': Math.max(1, Number(this.form.res_720p) || 1),
                    '1080p': Math.max(1, Number(this.form.res_1080p) || 1),
                };
                const durMap = {
                    5: Math.max(1, Number(this.form.dur_5) || 1),
                    8: Math.max(1, Number(this.form.dur_8) || 1),
                    12: Math.max(1, Number(this.form.dur_12) || 1),
                };
                const audioMap = {
                    false: Math.max(1, Number(this.form.audio_no) || 1),
                    true: Math.max(1, Number(this.form.audio_yes) || 1),
                };

                const list = [];
                const resolutions = ['480p', '720p', '1080p'];
                const durations = [5, 8, 12];
                const audioOptions = [false, true];

                for (const res of resolutions) {
                    for (const dur of durations) {
                        for (const hasAudio of audioOptions) {
                            const rM = resMap[res];
                            const dM = durMap[dur];
                            const aM = audioMap[hasAudio];
                            const total = base * rM * dM * aM;

                            list.push({
                                resolution: res,
                                duration: dur + 's',
                                audio: hasAudio ? 'Yes (Audio)' : 'No (Mute)',
                                has_audio: hasAudio,
                                formula: `${base} (Base) × ${rM} (${res}) × ${dM} (${dur}s) × ${aM} (${hasAudio ? 'Audio' : 'Mute'})`,
                                total: total
                            });
                        }
                    }
                }

                return list;
            }
        };
    }
</script>
@endsection
