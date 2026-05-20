<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>{{ __('Impact Report') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #1e293b;
            background: #ffffff;
            padding: 40px 48px;
        }

        /* ── Header ── */
        .header {
            border-bottom: 3px solid #0f766e;
            padding-bottom: 18px;
            margin-bottom: 28px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        .org-name {
            font-size: 20px;
            font-weight: 700;
            color: #0f766e;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 14px;
            color: #475569;
            margin-top: 4px;
        }
        .generated-at {
            font-size: 10px;
            color: #94a3b8;
            text-align: right;
        }

        /* ── Outreach info ── */
        .outreach-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 28px;
        }
        .outreach-box .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #16a34a;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .outreach-box .name {
            font-size: 16px;
            font-weight: 700;
            color: #14532d;
        }
        .outreach-meta {
            margin-top: 6px;
            font-size: 11px;
            color: #4b5563;
        }
        .outreach-meta span { margin-right: 20px; }

        /* ── Section heading ── */
        .section-heading {
            font-size: 13px;
            font-weight: 700;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 16px;
        }

        /* ── Stats grid ── */
        .stats-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .stats-grid td {
            width: 50%;
            padding: 0 8px 16px 0;
            vertical-align: top;
        }
        .stat-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 16px 20px;
            height: 80px;
        }
        .stat-card.highlight {
            background: #ecfdf5;
            border-color: #6ee7b7;
        }
        .stat-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            line-height: 1;
        }
        .stat-card.highlight .stat-value { color: #065f46; }
        .stat-desc {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 4px;
        }

        /* ── Wide stat (full-width row) ── */
        .stat-wide {
            background: #0f766e;
            border-radius: 8px;
            padding: 18px 24px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-wide .sw-label {
            font-size: 11px;
            color: #a7f3d0;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }
        .stat-wide .sw-value {
            font-size: 40px;
            font-weight: 700;
            color: #ffffff;
            line-height: 1;
        }
        .stat-wide .sw-desc {
            font-size: 10px;
            color: #6ee7b7;
            margin-top: 2px;
        }
        .stat-wide .sw-right { text-align: right; }

        /* ── Breakdown table ── */
        .breakdown-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .breakdown-table th {
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #64748b;
            padding: 8px 12px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }
        .breakdown-table td {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
            color: #1e293b;
        }
        .breakdown-table tr:nth-child(even) td { background: #f8fafc; }
        .breakdown-table .td-number {
            font-weight: 700;
            font-size: 14px;
            text-align: right;
            color: #0f766e;
        }
        .breakdown-table .td-pct {
            font-size: 11px;
            color: #64748b;
            text-align: right;
        }

        /* ── Progress bar ── */
        .progress-wrap {
            background: #e2e8f0;
            border-radius: 4px;
            height: 8px;
            width: 100%;
            margin-top: 4px;
        }
        .progress-fill {
            background: #0f766e;
            border-radius: 4px;
            height: 8px;
        }

        /* ── Footer ── */
        .footer {
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            margin-top: 10px;
            font-size: 9px;
            color: #94a3b8;
            display: flex;
            justify-content: space-between;
        }

        /* ── Confidential band ── */
        .confidential {
            background: #fef9c3;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 8px 12px;
            font-size: 10px;
            color: #92400e;
            text-align: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header">
        <div class="header-top">
            <div>
                <div class="org-name">Medical Outreach Programme</div>
                <div class="report-title">{{ __('Community Health Impact Report') }}</div>
            </div>
            <div class="generated-at">
                {{ __('Generated') }}: {{ $generatedAt }}<br>
                {{ __('For internal and donor use') }}
            </div>
        </div>
    </div>

    {{-- Confidential notice --}}
    <div class="confidential">
        {{ __('This document contains aggregated health service data. Individual patient records are not disclosed in this report.') }}
    </div>

    {{-- Outreach scope --}}
    @if ($outreach)
        <div class="outreach-box">
            <div class="label">{{ __('Outreach scope') }}</div>
            <div class="name">{{ $outreach->name }}</div>
            <div class="outreach-meta">
                @if ($outreach->location)
                    <span>📍 {{ $outreach->location }}</span>
                @endif
                @if ($outreach->start_date)
                    <span>📅 {{ $outreach->start_date->format('d M Y') }}
                        @if ($outreach->end_date && $outreach->end_date->ne($outreach->start_date))
                            – {{ $outreach->end_date->format('d M Y') }}
                        @endif
                    </span>
                @endif
            </div>
        </div>
    @else
        <div class="outreach-box">
            <div class="label">{{ __('Outreach scope') }}</div>
            <div class="name">{{ __('All outreaches (cumulative totals)') }}</div>
        </div>
    @endif

    {{-- Total checked-in banner --}}
    <div class="stat-wide">
        <div>
            <div class="sw-label">{{ __('Total Beneficiaries Served') }}</div>
            <div class="sw-desc">{{ __('Total number of community members who checked in') }}</div>
        </div>
        <div class="sw-right">
            <div class="sw-value">{{ number_format($stats['total_checked_in']) }}</div>
            <div class="sw-desc">{{ __('check-ins recorded') }}</div>
        </div>
    </div>

    {{-- Care breakdown heading --}}
    <div class="section-heading">{{ __('Care Delivered by Service Type') }}</div>

    @php
        $total = $stats['total_checked_in'] ?: 1;
        $rows = [
            [
                'label'  => __('General Medical Care'),
                'desc'   => __('Consultations, diagnoses and prescriptions by medical doctors'),
                'count'  => $stats['general_care'],
            ],
            [
                'label'  => __('Dental Care'),
                'desc'   => __('Oral health examinations and treatments'),
                'count'  => $stats['dental_care'],
            ],
            [
                'label'  => __('Eye Care'),
                'desc'   => __('Vision screening and eye examinations'),
                'count'  => $stats['eye_care'],
            ],
        ];
    @endphp

    <table class="breakdown-table">
        <thead>
            <tr>
                <th style="width:42%">{{ __('Service type') }}</th>
                <th style="width:35%">{{ __('Description') }}</th>
                <th style="width:12%">{{ __('Recipients') }}</th>
                <th style="width:11%">{{ __('% of check-ins') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                @php $pct = $total > 0 ? round($row['count'] / $total * 100, 1) : 0; @endphp
                <tr>
                    <td><strong>{{ $row['label'] }}</strong></td>
                    <td style="font-size:10px;color:#64748b">{{ $row['desc'] }}</td>
                    <td class="td-number">{{ number_format($row['count']) }}</td>
                    <td class="td-pct">{{ $pct }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- All-interventions highlight --}}
    @php $allPct = $total > 0 ? round($stats['all_interventions'] / $total * 100, 1) : 0; @endphp
    <table class="stats-grid">
        <tr>
            <td>
                <div class="stat-card highlight">
                    <div class="stat-label">{{ __('Received All Three Services') }}</div>
                    <div class="stat-value">{{ number_format($stats['all_interventions']) }}</div>
                    <div class="stat-desc">{{ $allPct }}% {{ __('of check-ins received general, dental and eye care') }}</div>
                </div>
            </td>
            <td>
                <div class="stat-card">
                    <div class="stat-label">{{ __('Total Care Interactions') }}</div>
                    <div class="stat-value">{{ number_format($stats['general_care'] + $stats['dental_care'] + $stats['eye_care']) }}</div>
                    <div class="stat-desc">{{ __('Sum of all delivered service lines') }}</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        <span>Medical Outreach Programme — Impact Report</span>
        <span>{{ __('Report scope') }}: {{ $outreach ? $outreach->name : __('All outreaches') }} · {{ $generatedAt }}</span>
    </div>

</body>
</html>
