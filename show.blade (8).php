@extends('layouts.admin')
@section('content')

    <style>
        .superflix-calendar {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            overflow: hidden;
        }
        
        .calendar-header {
            background: rgba(30, 41, 59, 0.9);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            padding: 20px;
        }
        
        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .calendar-title {
            font-size: 28px;
            font-weight: 700;
            color: white;
            text-align: center;
            flex: 1;
        }
        
        .nav-button {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.3);
            border-radius: 8px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #60a5fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .nav-button:hover {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
        }
        
        .view-tabs {
            display: flex;
            background: rgba(30, 41, 59, 0.8);
            border-radius: 12px;
            padding: 4px;
            gap: 4px;
            justify-content: center;
        }
        
        .view-tab {
            padding: 8px 20px;
            border-radius: 8px;
            font-weight: 500;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .view-tab.active {
            background: rgba(59, 130, 246, 0.8);
            color: white;
        }
        
        .view-tab:hover:not(.active) {
            background: rgba(59, 130, 246, 0.4);
            color: white;
        }
        
        .calendar-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 12px;
            margin: 20px 0;
        }
        
        .stat-card {
            background: rgba(30, 41, 59, 0.6);
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: white;
        }
        
        .stat-label {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }
        
        .sync-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }
        
        .sync-button {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 16px;
            color: white;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .sync-button:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        .sync-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .sync-button.series {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .sync-button.anime {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .sync-button.retry {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        .sync-progress {
            display: none;
            margin-top: 16px;
            padding: 16px;
            background: rgba(30, 41, 59, 0.6);
            border-radius: 8px;
            border: 1px solid rgba(59, 130, 246, 0.2);
        }
        
        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .progress-title {
            color: white;
            font-weight: 600;
        }
        
        .progress-stats {
            font-size: 12px;
            color: #94a3b8;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: rgba(75, 85, 99, 0.5);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            border-radius: 4px;
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .calendar-container {
            position: relative;
        }
        
        .calendar-view {
            display: none;
        }
        
        .calendar-view.active {
            display: block;
        }
        
        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: rgba(59, 130, 246, 0.1);
            padding: 15px;
            margin: 0;
        }
        
        .weekday {
            text-align: center;
            font-weight: 600;
            color: #94a3b8;
            font-size: 14px;
            padding: 10px;
        }
        
        .calendar-grid {
            display: grid !important;
            grid-template-columns: repeat(7, 1fr) !important;
            gap: 1px !important;
            background: rgba(59, 130, 246, 0.1) !important;
            padding: 0 15px 15px 15px !important;
        }
        
        .calendar-day-cell {
            background: rgba(30, 41, 59, 0.6) !important;
            border: 1px solid rgba(59, 130, 246, 0.1) !important;
            border-radius: 12px !important;
            min-height: 140px !important;
            padding: 12px !important;
            position: relative !important;
            display: flex !important;
            flex-direction: column !important;
            transition: all 0.3s ease !important;
        }
        
        .calendar-day-cell:hover {
            border-color: rgba(59, 130, 246, 0.4) !important;
            background: rgba(30, 41, 59, 0.8) !important;
        }
        
        .calendar-day-cell.has-events {
            cursor: pointer !important;
        }
        
        .calendar-day-cell.has-events:hover {
            border-color: rgba(59, 130, 246, 0.6) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.15) !important;
        }
        
        .calendar-day-cell.hidden {
            display: none !important;
        }
        
        .calendar-day-number {
            font-weight: 700 !important;
            color: white !important;
            font-size: 16px !important;
            margin-bottom: 8px !important;
        }
        
        .releases-count {
            position: absolute;
            top: 8px;
            right: 8px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border-radius: 6px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }
        
        .releases-count.anime-only {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .releases-count.series-only {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
        }
        
        .poster-grid {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 4px !important;
            flex-grow: 1 !important;
            margin-top: 8px !important;
        }
        
        .poster-item {
            width: 100% !important;
            border-radius: 6px !important;
            overflow: hidden !important;
            background: rgba(55, 65, 81, 0.8) !important;
            aspect-ratio: 3/4 !important;
            position: relative !important;
            transition: transform 0.2s ease !important;
        }
        
        .poster-item:hover {
            transform: scale(1.05) !important;
        }
        
        .poster-item img {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
        }
        
        .poster-item.anime::after {
            content: 'A';
            position: absolute;
            top: 2px;
            left: 2px;
            background: #f59e0b;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        .poster-item.series::after {
            content: 'S';
            position: absolute;
            top: 2px;
            left: 2px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        .poster-item.pending {
            border: 2px solid #f59e0b;
            opacity: 0.8;
        }
        
        .poster-item.synced {
            border: 2px solid #10b981;
        }
        
        .poster-item.pending::before {
            content: 'P';
            position: absolute;
            top: 2px;
            right: 2px;
            background: #f59e0b;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            z-index: 2;
        }
        
        .poster-item.synced::before {
            content: '✓';
            position: absolute;
            top: 2px;
            right: 2px;
            background: #10b981;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            font-weight: bold;
            z-index: 2;
        }
        
        .more-indicator {
            position: absolute !important;
            bottom: 6px !important;
            right: 6px !important;
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%) !important;
            color: white !important;
            border-radius: 50% !important;
            width: 22px !important;
            height: 22px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 10px !important;
            font-weight: bold !important;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.4) !important;
        }
        
        .content-filter {
            display: flex;
            background: rgba(30, 41, 59, 0.8);
            border-radius: 8px;
            padding: 4px;
            gap: 4px;
            margin-top: 16px;
        }
        
        .filter-tab {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 500;
            color: #94a3b8;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
            text-align: center;
        }
        
        .filter-tab.active {
            background: rgba(59, 130, 246, 0.8);
            color: white;
        }
        
        .filter-tab:hover:not(.active) {
            background: rgba(59, 130, 246, 0.4);
            color: white;
        }
        
        .calendar-modal {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .calendar-modal.show {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 450px;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(59, 130, 246, 0.2);
            transform: scale(0.95) translateX(100px);
            opacity: 0;
            transition: all 0.3s ease;
            position: fixed;
            right: 20px;
            top: 50%;
            transform-origin: center;
        }
        
        .calendar-modal.show .modal-content {
            transform: scale(1) translateY(-50%);
            opacity: 1;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid rgba(59, 130, 246, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: white;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 24px;
            cursor: pointer;
            padding: 5px;
            transition: color 0.3s ease;
        }
        
        .modal-close:hover {
            color: white;
        }
        
        .modal-body {
            padding: 15px 20px;
            overflow-y: auto;
            max-height: calc(90vh - 80px);
        }
        
        .episode-item {
            background: rgba(30, 41, 59, 0.8);
            border: 1px solid rgba(59, 130, 246, 0.1);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .episode-item.api-updated {
            border-left: 4px solid #10b981;
        }
        
        .episode-item.api-late {
            border-left: 4px solid #f59e0b;
        }
        
        .episode-item.local-synced {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.15) 0%, rgba(5, 150, 105, 0.15) 100%);
        }
        
        .episode-item.local-pending {
            background: rgba(30, 41, 59, 0.8);
        }
        
        .episode-item.anime::before {
            content: 'ANIME';
            position: absolute;
            top: 8px;
            left: 8px;
            background: #f59e0b;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .episode-item.series::before {
            content: 'SÉRIE';
            position: absolute;
            top: 8px;
            left: 8px;
            background: #3b82f6;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: bold;
        }
        
        .episode-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            margin-top: 20px;
        }
        
        .episode-status-group {
            display: flex;
            gap: 6px;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-api-updated {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .status-api-late {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .status-local-synced {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
            color: white;
        }
        
        .status-local-pending {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }
        
        .status-episode-exists {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
        }
        
        .episode-title {
            color: white;
            font-weight: 700;
            font-size: 15px;
            margin-bottom: 4px;
            line-height: 1.3;
        }
        
        .episode-number {
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 12px;
        }
        
        .episode-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .sync-single-button {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            color: white;
            font-size: 11px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .sync-single-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        
        .sync-single-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .loading-spinner {
            width: 12px;
            height: 12px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>

    <div class="container-fluid">
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden dark:bg-gray-900 dark:border-gray-800">
            <div class="border-b border-gray-200 dark:border-gray-700">
                <nav class="flex space-x-4 -mb-px px-5" aria-label="Tabs">
                    <button id="tab-import" class="main-tab-button cursor-pointer whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-blue-500 text-blue-600">
                        Importar do TMDB
                    </button>
                    <button id="tab-calendar" class="main-tab-button cursor-pointer whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Calendário de Lançamentos
                    </button>
                </nav>
            </div>

            <div id="content-import" class="main-tab-content">
                <form method="post" action="{{route('admin.tmdb.fetch')}}" class="grid grid-cols-1 lg:grid-cols-12 lg:gap-x-8 gap-4 px-5 py-4">
                    @csrf
                    <div class="lg:col-span-2">
                        <x-form.select name="type">
                            @foreach(config('attr.tmdb.type') as $key => $value)
                                <option value="{{$key}}" @if(isset($request->type) AND $key == $request->type){{'selected'}}@endif>{{__($value)}}</option>
                            @endforeach
                        </x-form.select>
                    </div>
                    <div class="lg:col-span-6">
                        <x-form.input type="text" name="q" placeholder="{{__('Search')}}" value="{{old('q') ?? $request->q}}"/>
                    </div>
                    <div class="lg:col-span-2">
                        <x-form.select name="sortable">
                            @foreach(config('attr.tmdb.sortable') as $key => $value)
                                <option value="{{$key}}">{{__($value)}}</option>
                            @endforeach
                        </x-form.select>
                    </div>
                    <div class="lg:col-span-2">
                        <x-form.secondary type="submit" class="w-full">{{__('Fetch data')}}</x-form.secondary>
                    </div>
                </form>

                <div class="border-t border-gray-200 dark:border-gray-800 px-5 py-4">
                    <h3 class="text-lg font-medium text-gray-800 dark:text-gray-200 mb-3">{{ __('Bulk Import (Client-side)') }}</h3>
                    <div id="bulk-import-container">
                        <div id="bulk-import-form">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                                <div class="md:col-span-2">
                                    <x-form.select name="bulk_type" id="bulk_type">
                                        @foreach(config('attr.tmdb.type') as $key => $value)
                                            <option value="{{$key}}">{{__($value)}}</option>
                                        @endforeach
                                    </x-form.select>
                                </div>
                                <div class="md:col-span-8">
                                    <x-form.textarea name="bulk_ids" id="bulk_ids" placeholder="{{ __('Enter TMDB or IMDb IDs, one per line or separated by commas.') }}" rows="4"></x-form.textarea>
                                </div>
                                <div class="md:col-span-2">
                                    <x-form.secondary type="button" id="start-bulk-import" class="w-full h-full">{{ __('Import IDs') }}</x-form.secondary>
                                </div>
                            </div>
                        </div>
                        
                        <div id="bulk-import-status" class="mt-4 hidden">
                            <div class="flex justify-between items-center">
                                <h4 class="text-md font-semibold text-gray-700 dark:text-gray-300">Importação em Progresso</h4>
                                <button id="new-import-button" class="text-sm text-blue-600 hover:underline hidden">{{ __('Start New Import') }}</button>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700 mt-2">
                                <div id="progress-bar" class="bg-blue-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <div class="flex justify-between text-sm font-medium text-gray-600 dark:text-gray-400 mt-1">
                                <span id="progress-text">Processando 0 de 0...</span>
                                <div>
                                    <span class="text-green-500">Sucesso: <span id="success-count">0</span></span> | 
                                    <span class="text-orange-500">Ignorados: <span id="skipped-count">0</span></span> |
                                    <span class="text-red-500">Falhas: <span id="failed-count">0</span></span>
                                </div>
                            </div>
                            <div id="job-details" class="mt-4 space-y-2 max-h-60 overflow-y-auto pr-2">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="">
                    <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left">
                                <div class="text-xs font-medium tracking-tight text-gray-700 dark:text-gray-200">{{__('Heading')}}</div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left">
                                <div class="text-xs font-medium tracking-tight text-gray-700 dark:text-gray-200">{{__('Release date')}}</div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left">
                                <div class="text-xs font-medium tracking-tight text-gray-700 dark:text-gray-200">{{__('Popularity')}}</div>
                            </th>
                            <th scope="col" class="px-6 py-4 text-right"></th>
                        </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @if(isset($listings))
                            @foreach($listings as $listing)
                                <tr class="form{{$listing['id']}}">
                                    <td class="h-px w-px whitespace-nowrap">
                                        <div class="px-6 py-3">
                                            <a class="text-sm text-gray-600 dark:text-gray-200 flex items-center space-x-6 group" href="#">
                                                <div class="aspect-[2/3] bg-gray-100 rounded-md w-14 overflow-hidden relative">
                                                    <img src="{{$listing['image']}}" class="absolute inset-0 object-cover">
                                                </div>
                                                <div>
                                                    <div class="font-medium group-hover:underline mb-2">{{$listing['title']}}</div>
                                                    <div class="text-xs text-gray-400 dark:text-gray-500">{{Str::limit($listing['overview'],80)}}</div>
                                                </div>
                                            </a>
                                        </div>
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">
                                        <div class="px-6 py-3">
                                            <div class="text-sm text-gray-400 dark:text-gray-500">{{date('Y', strtotime($listing['release_date']))}}</div>
                                        </div>
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">
                                        <div class="px-6 py-3 flex items-center space-x-6">
                                            <div class="flex max-w-[100px] w-full h-2 bg-gray-100 rounded-full overflow-hidden dark:bg-gray-700">
                                                <div class="flex flex-col justify-center rounded-full overflow-hidden @if($listing['vote_average'] <5){{'bg-red-500'}}@elseif($listing['vote_average'] >=5 AND $listing['vote_average'] <= 7){{'bg-orange-400'}}@elseif($listing['vote_average'] >7){{'bg-emerald-500'}}@endif"
                                                    role="progressbar" style="width: {{($listing['vote_average'] / 10) * 100}}%"
                                                    aria-valuenow="78" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <div class="text-sm font-medium text-gray-500 dark:text-gray-300">{{$listing['vote_average']}}</div>
                                        </div>
                                    </td>
                                    <td class="h-px w-px whitespace-nowrap">
                                        <div class="px-6 py-3 flex justify-end">
                                            <form method="post" action="{{route('admin.tmdb.store')}}" data-id="{{$listing['id']}}" class="ajax-form">
                                                @csrf
                                                <input type="hidden" name="tmdb_id" value="{{$listing['id']}}">
                                                <input type="hidden" name="type" value="{{$listing['type']}}">
                                                <x-form.secondary class="!px-6" size="sm" type="submit">{{__('Import')}}</x-form.secondary>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                        </tbody>
                    </table>
                </div>
                @if(isset($result['total_results']) AND $result['total_results'] > 0)
                    @include('admin.tmdb.pagination')
                @endif
            </div>

            <div id="content-calendar" class="main-tab-content hidden">
                <div class="superflix-calendar">
                    <div class="calendar-header">
                        <div class="calendar-nav">
                            <button class="nav-button" id="prev-month">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="15,18 9,12 15,6"></polyline>
                                </svg>
                            </button>
                            
                            <h1 class="calendar-title" id="current-month-year">
                                {{ Carbon\Carbon::now()->translatedFormat('F Y') }}
                            </h1>
                            
                            <button class="nav-button" id="next-month">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9,18 15,12 9,6"></polyline>
                                </svg>
                            </button>
                        </div>
                        
                        <div class="view-tabs">
                            <div class="view-tab active" data-view="month">Mês</div>
                            <div class="view-tab" data-view="week">Semana</div>
                            <div class="view-tab" data-view="day">Dia</div>
                        </div>

                        @if(isset($calendarStats))
                        <div class="calendar-stats">
                            <div class="stat-card">
                                <div class="stat-number">{{ $calendarStats['total'] }}</div>
                                <div class="stat-label">Total de Items</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">{{ $calendarStats['series'] }}</div>
                                <div class="stat-label">Séries</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">{{ $calendarStats['animes'] }}</div>
                                <div class="stat-label">Animes</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">{{ $calendarStats['synchronized'] }}</div>
                                <div class="stat-label">Sincronizados</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number">{{ $calendarStats['pending'] }}</div>
                                <div class="stat-label">Pendentes</div>
                            </div>
                        </div>
                        @endif
                        
                        <div class="sync-controls">
                            <button class="sync-button" id="sync-all-button" data-sync-type="all">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0011.664 0l3.18-3.185" />
                                </svg>
                                Sincronizar Tudo
                            </button>
                            <button class="sync-button series" id="sync-series-button" data-sync-type="series">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 3.104v5.714a2.25 2.25 0 01-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 014.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 14.5M14.25 3.104c.251.023.501.05.75.082M19.8 14.5l-2.436 2.436a2.25 2.25 0 01-3.182 0l-5.434-5.435M19.8 14.5V16a2.25 2.25 0 01-2.25 2.25h-5.25m-5.25-14v5.714c0 .597-.237 1.17-.659 1.591L4.204 15.5M2.204 15.5l2.436 2.436a2.25 2.25 0 003.182 0l5.434-5.435M2.204 15.5V14a2.25 2.25 0 012.25-2.25H9.75" />
                                </svg>
                                Sincronizar Séries
                            </button>
                            <button class="sync-button anime" id="sync-anime-button" data-sync-type="anime">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z" />
                                </svg>
                                Sincronizar Animes
                            </button>
                            <button class="sync-button retry" id="retry-sync-button" style="display: none;">
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0011.664 0l3.18-3.185" />
                                </svg>
                                Tentar Novamente
                            </button>
                        </div>

                        <div class="sync-progress" id="sync-progress">
                            <div class="progress-header">
                                <div class="progress-title" id="progress-title">Sincronizando...</div>
                                <div class="progress-stats" id="progress-stats">0/0 processados</div>
                            </div>
                            <div class="progress-bar-container">
                                <div class="progress-bar" id="sync-progress-bar"></div>
                            </div>
                            <div class="progress-stats">
                                <span class="text-green-400">Criados: <span id="created-count">0</span></span> |
                                <span class="text-blue-400">Atualizados: <span id="updated-count">0</span></span> |
                                <span class="text-yellow-400">Ignorados: <span id="sync-skipped-count">0</span></span> |
                                <span class="text-red-400">Falhas: <span id="sync-failed-count">0</span></span>
                            </div>
                        </div>

                        <div class="content-filter">
                            <div class="filter-tab active" data-filter="all">Todos</div>
                            <div class="filter-tab" data-filter="series">Séries</div>
                            <div class="filter-tab" data-filter="anime">Animes</div>
                            <div class="filter-tab" data-filter="pending">Pendentes</div>
                            <div class="filter-tab" data-filter="synced">Sincronizados</div>
                        </div>
                    </div>

                    @if(isset($calendarByMonth) && $calendarByMonth->isNotEmpty())
                        <div class="calendar-container">
                            @foreach($calendarByMonth as $monthKey => $itemsForMonth)
                                @php
                                    $monthDate = \Carbon\Carbon::createFromFormat('Y-m', $monthKey);
                                    $daysInMonth = $monthDate->daysInMonth;
                                    $startDayOfWeek = $monthDate->startOfMonth()->dayOfWeek;
                                    $daysWithEvents = $itemsForMonth->groupBy(fn($item) => \Carbon\Carbon::parse($item['air_date'])->format('d'));
                                @endphp

                                <div class="calendar-view month-view @if($loop->first) active @endif" 
                                     data-view="month" 
                                     data-month="{{ $monthKey }}">
                                    <div class="calendar-weekdays">
                                        <div class="weekday">Dom</div>
                                        <div class="weekday">Seg</div>
                                        <div class="weekday">Ter</div>
                                        <div class="weekday">Qua</div>
                                        <div class="weekday">Qui</div>
                                        <div class="weekday">Sex</div>
                                        <div class="weekday">Sáb</div>
                                    </div>
                                    
                                    <div class="calendar-grid">
                                        @for ($i = 0; $i < $startDayOfWeek; $i++)
                                            <div class="calendar-day-cell"></div>
                                        @endfor

                                        @for ($d = 1; $d <= $daysInMonth; $d++)
                                            @php
                                                $dayKey = str_pad($d, 2, '0', STR_PAD_LEFT);
                                                $hasEvents = $daysWithEvents->has($dayKey);
                                                $dayEvents = $hasEvents ? $daysWithEvents->get($dayKey) : collect();
                                                
                                                $hasAnime = $dayEvents->filter(fn($i) => (int)($i['type'] ?? 0) === 3)->isNotEmpty();
                                                $hasSeries = $dayEvents->filter(fn($i) => (int)($i['type'] ?? 0) === 2)->isNotEmpty();
                                                $hasPending = $dayEvents->where('local_status', 'Pendente')->isNotEmpty();
                                                $hasSynced = $dayEvents->where('local_status', 'Sincronizado')->isNotEmpty();
                                                
                                                $countBadgeClass = '';
                                                if ($hasAnime && !$hasSeries) {
                                                    $countBadgeClass = 'anime-only';
                                                } elseif ($hasSeries && !$hasAnime) {
                                                    $countBadgeClass = 'series-only';
                                                }

                                                $fullDateIso = $monthDate->format('Y-m') . '-' . str_pad($d, 2, '0', STR_PAD_LEFT);
                                            @endphp
                                            <div class="calendar-day-cell {{ $hasEvents ? 'has-events js-show-day-details' : '' }}"
                                                 data-day="{{ $d }}"
                                                 data-date-iso="{{ $fullDateIso }}"
                                                 data-filter-anime="{{ $hasAnime ? 'true' : 'false' }}"
                                                 data-filter-series="{{ $hasSeries ? 'true' : 'false' }}"
                                                 data-filter-pending="{{ $hasPending ? 'true' : 'false' }}"
                                                 data-filter-synced="{{ $hasSynced ? 'true' : 'false' }}"
                                                 @if($hasEvents)
                                                     data-events="{{ $dayEvents->toJson() }}"
                                                     data-date="{{ $d }} de {{ $monthDate->translatedFormat('F') }}"
                                                 @endif
                                            >
                                                <div class="calendar-day-number">{{ $d }}</div>
                                                @if($hasEvents)
                                                    <div class="releases-count {{ $countBadgeClass }}">{{ $dayEvents->count() }} lanç.</div>
                                                    <div class="poster-grid">
                                                        @foreach($dayEvents->take(4) as $item)
                                                            @php
                                                                $poster = $item['poster'] ?? $item['poster_path'] ?? null;
                                                                $t = (int)($item['type'] ?? 0);
                                                                $contentTypeClass = $t === 3 ? 'anime' : ($t === 2 ? 'series' : 'series');
                                                            @endphp
                                                            <div class="poster-item {{ $contentTypeClass }}">
                                                                @if($poster)
                                                                    <img src="https://image.tmdb.org/t/p/w200{{ $poster }}"
                                                                         alt="{{ $item['title'] ?? 'Título' }}"
                                                                         onerror="this.style.display='none'"
                                                                         title="{{ $item['title'] ?? 'Título' }} - S{{$item['season'] ?? 0}}E{{$item['number'] ?? 0}}">
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    @if($dayEvents->count() > 4)
                                                        <div class="more-indicator">+{{ $dayEvents->count() - 4 }}</div>
                                                    @endif
                                                @endif
                                            </div>
                                        @endfor
                                    </div>
                                </div>
                            @endforeach

                            <div id="week-view" class="calendar-view" data-view="week"></div>
                            <div id="day-view" class="calendar-view" data-view="day"></div>
                        </div>
                    @elseif(isset($calendarError))
                        <div class="text-center py-16">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                               <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                           </svg>
                           <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-200">Erro ao carregar calendário</h3>
                           <p class="mt-1 text-sm text-red-500">{{ $calendarError }}</p>
                           <button class="mt-4 sync-button retry" onclick="window.location.reload();">
                               <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                   <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0011.664 0l3.18-3.185" />
                               </svg>
                               Tentar Novamente
                           </button>
                        </div>
                    @else
                        <div class="text-center py-16">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                               <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                           </svg>
                           <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-200">Nenhum lançamento encontrado</h3>
                           <p class="mt-1 text-sm text-gray-500">Não há lançamentos futuros no calendário no momento.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div id="calendar-modal" class="calendar-modal" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title" class="modal-title">Lançamentos</h3>
                <button id="modal-close" class="modal-close">&times;</button>
            </div>
            <div id="modal-body" class="modal-body">
                <!-- Conteúdo será injetado pelo JavaScript -->
            </div>
        </div>
    </div>

    @push('javascript')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                // Estado
                let currentDate = new Date();
                let currentView = 'month';

                // Utils data PT-BR
                const ptMonthNames = ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
                                      'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'];
                const ptWeekdayShort = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];

                const formatISO = (d) => {
                    const y = d.getFullYear();
                    const m = String(d.getMonth() + 1).padStart(2, '0');
                    const day = String(d.getDate()).padStart(2, '0');
                    return `${y}-${m}-${day}`;
                };

                const parseISO = (iso) => {
                    const [y, m, d] = iso.split('-').map(Number);
                    return new Date(y, m - 1, d);
                };

                const startOfWeek = (d) => {
                    const date = new Date(d);
                    const day = date.getDay(); // 0 domingo
                    date.setDate(date.getDate() - day);
                    return date;
                };

                const addDays = (d, n) => {
                    const date = new Date(d);
                    date.setDate(date.getDate() + n);
                    return date;
                };

                const addMonths = (d, n) => {
                    const date = new Date(d);
                    date.setMonth(date.getMonth() + n);
                    return date;
                };

                // Abas principais
                const mainTabs = document.querySelectorAll('.main-tab-button');
                const mainContents = document.querySelectorAll('.main-tab-content');
                
                mainTabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        mainTabs.forEach(t => {
                            t.classList.remove('border-blue-500', 'text-blue-600');
                            t.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
                        });
                        
                        tab.classList.add('border-blue-500', 'text-blue-600');
                        tab.classList.remove('border-transparent', 'text-gray-500');
                        
                        mainContents.forEach(c => c.classList.add('hidden'));
                        const contentId = tab.id.replace('tab-', 'content-');
                        const targetContent = document.getElementById(contentId);
                        if (targetContent) {
                            targetContent.classList.remove('hidden');
                        }
                    });
                });

                // Helpers filtro/render
                function parseEventsFromCell(cell) {
                    let rawData = cell.dataset.events || '[]';
                    rawData = rawData.replace(/&quot;/g, '"')
                                     .replace(/&#039;/g, "'")
                                     .replace(/&amp;/g, "&")
                                     .replace(/&lt;/g, "<")
                                     .replace(/&gt;/g, ">");
                    try {
                        const arr = JSON.parse(rawData);
                        return Array.isArray(arr) ? arr : [];
                    } catch {
                        return [];
                    }
                }

                function encodeHtml(str) {
                    return str.replace(/&/g, '&amp;')
                              .replace(/"/g, '&quot;')
                              .replace(/'/g, '&#039;')
                              .replace(/</g, '&lt;')
                              .replace(/>/g, '&gt;');
                }

                function writeEventsToCell(cell, events) {
                    const encoded = encodeHtml(JSON.stringify(events));
                    cell.dataset.events = encoded;
                }

                function rebuildCellPosters(cell, filter) {
                    const events = parseEventsFromCell(cell);

                    let filtered = events;
                    if (filter === 'anime') {
                        filtered = events.filter(e => parseInt(e.type ?? 0, 10) === 3);
                    } else if (filter === 'series') {
                        filtered = events.filter(e => parseInt(e.type ?? 0, 10) === 2);
                    } else if (filter === 'pending') {
                        filtered = events.filter(e => (e.local_status || 'Pendente') === 'Pendente');
                    } else if (filter === 'synced') {
                        filtered = events.filter(e => (e.local_status || 'Pendente') === 'Sincronizado');
                    }

                    const badge = cell.querySelector('.releases-count');
                    if (badge) {
                        badge.classList.remove('anime-only', 'series-only');
                        const hasAnime = filtered.some(e => parseInt(e.type ?? 0, 10) === 3);
                        const hasSeries = filtered.some(e => parseInt(e.type ?? 0, 10) === 2);
                        if (hasAnime && !hasSeries) badge.classList.add('anime-only');
                        else if (hasSeries && !hasAnime) badge.classList.add('series-only');
                        
                        // Update badge text based on filter
                        if (filter === 'pending') {
                            badge.textContent = `${filtered.length} pend.`;
                        } else if (filter === 'synced') {
                            badge.textContent = `${filtered.length} sinc.`;
                        } else {
                            badge.textContent = `${filtered.length} lanç.`;
                        }
                    }

                    // Limpa e recria grid
                    cell.querySelector('.poster-grid')?.remove();
                    cell.querySelector('.more-indicator')?.remove();

                    if (filtered.length > 0) {
                        const first4 = filtered.slice(0, 4);
                        let html = '<div class="poster-grid">';
                        first4.forEach(item => {
                            const poster = item.poster || item.poster_path || '';
                            const t = parseInt(item.type ?? 0, 10);
                            const cls = (t === 3) ? 'anime' : 'series';
                            const localStatus = item.local_status || 'Pendente';
                            const statusClass = localStatus === 'Sincronizado' ? 'synced' : 'pending';
                            html += `<div class="poster-item ${cls} ${statusClass}">`;
                            if (poster) {
                                const season = item.season || item.season_number || 0;
                                const ep = String(item.number || item.episode_number || 0).padStart(2, '0');
                                const title = item.title || 'Título';
                                html += `<img src="https://image.tmdb.org/t/p/w200${poster}" alt="${title}" onerror="this.style.display='none'" title="${title} - S${season}E${ep} (${localStatus})">`;
                            }
                            html += `</div>`;
                        });
                        html += `</div>`;
                        cell.insertAdjacentHTML('beforeend', html);

                        if (filtered.length > 4) {
                            cell.insertAdjacentHTML('beforeend', `<div class="more-indicator">+${filtered.length - 4}</div>`);
                        }
                    }
                }

                function applyContentFilter(filter) {
                    const cells = document.querySelectorAll('.month-view.active .calendar-day-cell');

                    cells.forEach(cell => {
                        cell.classList.remove('hidden');

                        if (filter === 'all') {
                            if (cell.classList.contains('has-events')) {
                                rebuildCellPosters(cell, 'all');
                            }
                            return;
                        }

                        const hasEvents = cell.classList.contains('has-events');
                        if (!hasEvents) {
                            cell.classList.add('hidden');
                            return;
                        }

                        let shouldShow = false;
                        switch (filter) {
                            case 'series':
                                shouldShow = cell.dataset.filterSeries === 'true';
                                break;
                            case 'anime':
                                shouldShow = cell.dataset.filterAnime === 'true';
                                break;
                            case 'pending':
                                shouldShow = cell.dataset.filterPending === 'true';
                                break;
                            case 'synced':
                                shouldShow = cell.dataset.filterSynced === 'true';
                                break;
                        }

                        if (!shouldShow) {
                            cell.classList.add('hidden');
                            return;
                        }

                        if (filter === 'series' || filter === 'anime') {
                            rebuildCellPosters(cell, filter);
                        } else if (filter === 'pending' || filter === 'synced') {
                            rebuildCellPosters(cell, filter);
                        } else {
                            rebuildCellPosters(cell, 'all');
                        }
                    });
                }

                function reapplyActiveFilter() {
                    const active = document.querySelector('.content-filter .filter-tab.active');
                    applyContentFilter(active ? active.dataset.filter : 'all');
                }

                // Filtros UI
                const filterTabs = document.querySelectorAll('.filter-tab');
                filterTabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        filterTabs.forEach(t => t.classList.remove('active'));
                        tab.classList.add('active');
                        applyContentFilter(tab.dataset.filter);
                    });
                });

                // View tabs (mês/semana/dia)
                const viewTabs = document.querySelectorAll('.view-tab');
                const monthYearTitle = document.getElementById('current-month-year');
                const weekViewEl = document.getElementById('week-view');
                const dayViewEl = document.getElementById('day-view');

                viewTabs.forEach(tab => {
                    tab.addEventListener('click', () => {
                        viewTabs.forEach(t => t.classList.remove('active'));
                        tab.classList.add('active');
                        currentView = tab.dataset.view || 'month';
                        updateCalendarView();
                    });
                });

                // Navegação
                const prevButton = document.getElementById('prev-month');
                const nextButton = document.getElementById('next-month');
                
                if (prevButton && nextButton) {
                    prevButton.addEventListener('click', () => {
                        if (currentView === 'month') {
                            currentDate = addMonths(currentDate, -1);
                        } else if (currentView === 'week') {
                            currentDate = addDays(currentDate, -7);
                        } else {
                            currentDate = addDays(currentDate, -1);
                        }
                        updateCalendarView();
                    });
                    
                    nextButton.addEventListener('click', () => {
                        if (currentView === 'month') {
                            currentDate = addMonths(currentDate, 1);
                        } else if (currentView === 'week') {
                            currentDate = addDays(currentDate, 7);
                        } else {
                            currentDate = addDays(currentDate, 1);
                        }
                        updateCalendarView();
                    });
                }

                function updateCalendarView() {
                    const monthKey = currentDate.getFullYear() + '-' + String(currentDate.getMonth() + 1).padStart(2, '0');
                    const monthViews = document.querySelectorAll('.month-view');

                    document.querySelectorAll('.calendar-view').forEach(v => v.classList.remove('active'));

                    if (currentView === 'month') {
                        monthViews.forEach(view => {
                            view.classList.remove('active');
                            if (view.dataset.month === monthKey) {
                                view.classList.add('active');
                            }
                        });
                        if (monthYearTitle) {
                            monthYearTitle.textContent = ptMonthNames[currentDate.getMonth()] + ' ' + currentDate.getFullYear();
                        }
                        reapplyActiveFilter();
                    } else if (currentView === 'week') {
                        if (weekViewEl) {
                            renderWeekView(currentDate);
                            weekViewEl.classList.add('active');
                        }
                        if (monthYearTitle) {
                            monthYearTitle.textContent = `Semana de ${formatDateBR(startOfWeek(currentDate))}`;
                        }
                    } else if (currentView === 'day') {
                        if (dayViewEl) {
                            renderDayView(currentDate);
                            dayViewEl.classList.add('active');
                        }
                        if (monthYearTitle) {
                            monthYearTitle.textContent = formatDateLongBR(currentDate);
                        }
                    }
                }

                function formatDateBR(d) {
                    const date = new Date(d);
                    const dd = String(date.getDate()).padStart(2, '0');
                    const mm = ptMonthNames[date.getMonth()];
                    const yyyy = date.getFullYear();
                    return `${dd} de ${mm} de ${yyyy}`;
                }

                function formatDateLongBR(d) {
                    const date = new Date(d);
                    const weekday = ptWeekdayShort[date.getDay()];
                    return `${weekday}, ${formatDateBR(date)}`;
                }

                // Eventos (a partir do Month View DOM)
                function getEventsForISO(iso) {
                    const [y, m, d] = iso.split('-');
                    const monthKey = `${y}-${m}`;
                    const monthView = document.querySelector(`.month-view[data-month="${monthKey}"]`);
                    if (!monthView) return [];
                    const dayCell = monthView.querySelector(`.calendar-day-cell[data-day="${parseInt(d, 10)}"]`);
                    if (!dayCell || !dayCell.dataset.events) return [];
                    return parseEventsFromCell(dayCell);
                }

                function renderPostersHtml(events) {
                    const firstFour = events.slice(0, 4);
                    let html = '<div class="poster-grid">';
                    firstFour.forEach(item => {
                        const poster = item.poster || item.poster_path || '';
                        const t = parseInt(item.type ?? 0, 10);
                        const cls = (t === 3) ? 'anime' : 'series';
                        const localStatus = item.local_status || 'Pendente';
                        const statusClass = localStatus === 'Sincronizado' ? 'synced' : 'pending';
                        html += `<div class="poster-item ${cls} ${statusClass}">`;
                        if (poster) {
                            const season = item.season || item.season_number || 0;
                            const ep = String(item.number || item.episode_number || 0).padStart(2, '0');
                            const title = item.title || 'Título';
                            html += `<img src="https://image.tmdb.org/t/p/w200${poster}" alt="${title}" onerror="this.style.display='none'" title="${title} - S${season}E${ep} (${localStatus})">`;
                        }
                        html += `</div>`;
                    });
                    html += `</div>`;
                    if (events.length > 4) {
                        html += `<div class="more-indicator">+${events.length - 4}</div>`;
                    }
                    return html;
                }

                function renderWeekView(baseDate) {
                    const start = startOfWeek(baseDate);
                    const days = Array.from({ length: 7 }, (_, i) => addDays(start, i));
                    const header = `
                        <div class="calendar-weekdays">
                            ${ptWeekdayShort.map(w => `<div class="weekday">${w}</div>`).join('')}
                        </div>
                    `;
                    let grid = `<div class="calendar-grid">`;
                    days.forEach(day => {
                        const iso = formatISO(day);
                        const events = getEventsForISO(iso);
                        const hasAnime = events.some(e => parseInt(e.type ?? 0,10) === 3);
                        const hasSeries = events.some(e => parseInt(e.type ?? 0,10) === 2);
                        let countClass = '';
                        if (hasAnime && !hasSeries) countClass = 'anime-only';
                        else if (hasSeries && !hasAnime) countClass = 'series-only';
                        grid += `
                            <div class="calendar-day-cell ${events.length ? 'has-events js-show-day-details' : ''}" 
                                 data-date-iso="${iso}" 
                                 data-date="${day.getDate()} de ${ptMonthNames[day.getMonth()]}"
                                 ${events.length ? `data-events="${encodeHtml(JSON.stringify(events))}"` : ''}>
                                <div class="calendar-day-number">${day.getDate()}</div>
                                ${events.length ? `<div class="releases-count ${countClass}">${events.length} lanç.</div>` : ''}
                                ${events.length ? renderPostersHtml(events) : ''}
                            </div>
                        `;
                    });
                    grid += `</div>`;
                    weekViewEl.innerHTML = header + grid;
                }

                function renderDayView(date) {
                    const iso = formatISO(date);
                    const events = getEventsForISO(iso);
                    let html = `
                        <div class="calendar-weekdays">
                            <div class="weekday" style="grid-column: span 7; text-align:left;">
                                ${formatDateLongBR(date)}
                            </div>
                        </div>
                        <div style="padding: 0 15px 15px 15px;">
                    `;
                    if (!events.length) {
                        html += `<div class="text-sm text-gray-300">Sem lançamentos neste dia.</div>`;
                    } else {
                        events.forEach(item => {
                            const localStatus = item.local_status || 'Pendente';
                            const apiStatus = item.status || 'Futuro';
                            const seriesTitle = item.title || 'Série Desconhecida';
                            const tmdbId = item.tmdb_id || '';
                            const seasonNumber = item.season || item.season_number || 1;
                            const episodeNumber = item.number || item.episode_number || 1;
                            const t = parseInt(item.type ?? 0, 10);
                            const contentType = (t === 3) ? 'anime' : 'series';
                            const isSynced = localStatus === 'Sincronizado';
                            const episodeExists = item.episode_exists || false;
                            const debugInfo = item.debug_info || '';
                            html += `
                                <div class="episode-item ${contentType} ${isSynced ? 'local-synced' : 'local-pending'} ${(apiStatus === 'Atualizado') ? 'api-updated' : 'api-late'} js-modal-item" data-tmdb-id="${tmdbId}" data-type="tv">
                                    <div class="episode-header">
                                        <div class="episode-status-group">
                                            <span class="status-badge ${(apiStatus === 'Atualizado') ? 'status-api-updated' : 'status-api-late'}">
                                                API: ${(apiStatus === 'Atualizado') ? 'Atualizado' : (apiStatus || 'Desconhecido')}
                                            </span>
                                            <span class="status-badge ${isSynced ? 'status-local-synced' : 'status-local-pending'} status-local">
                                                Local: ${isSynced ? 'Sincronizado' : 'Pendente'}
                                            </span>
                                            ${episodeExists && !isSynced ? `<span class="status-badge status-episode-exists">Ep. Existe</span>` : ''}
                                        </div>
                                    </div>
                                    <div class="episode-title">${seriesTitle}</div>
                                    <div class="episode-number">T${seasonNumber}E${String(episodeNumber).padStart(2, '0')}</div>
                                    <div class="episode-debug" style="font-size: 10px; color: #666; margin-top: 4px;">${debugInfo}</div>
                                    <div class="episode-actions">
                                        <button class="sync-single-button js-sync-single-item" title="Sincronizar Série">
                                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0011.664 0l3.18-3.185m-3.181-4.991v4.991h-4.992a4.5 4.5 0 01-4.5-4.5v-4.5m0 0h4.993v4.992h-4.993v-4.992z" />
                                            </svg>
                                            Sincronizar Série
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                    }
                    html += `</div>`;
                    dayViewEl.innerHTML = html;
                }

                // Inicial
                updateCalendarView();

                // Click dia -> modal
                document.addEventListener('click', (e) => {
                    const dayCell = e.target.closest('.js-show-day-details');
                    if (dayCell) {
                        const iso = dayCell.dataset.dateIso;
                        if (iso) {
                            currentDate = parseISO(iso);
                        }
                        try {
                            let rawData = dayCell.dataset.events || '[]';
                            rawData = rawData.replace(/&quot;/g, '"')
                                             .replace(/&#039;/g, "'")
                                             .replace(/&amp;/g, "&")
                                             .replace(/&lt;/g, "<")
                                             .replace(/&gt;/g, ">");
                            
                            const eventsData = JSON.parse(rawData);
                            const date = dayCell.dataset.date || formatDateLongBR(currentDate);
                            openModal(date, eventsData);
                        } catch (error) {
                            console.error('Error parsing events data:', error);
                        }
                    }
                });

                // Modal
                const modal = document.getElementById('calendar-modal');
                const modalTitle = document.getElementById('modal-title');
                const modalBody = document.getElementById('modal-body');
                const modalClose = document.getElementById('modal-close');

                const openModal = (date, eventsData) => {
                    if (!modal || !modalTitle || !modalBody) return;
                    
                    modalTitle.textContent = date;
                    modalBody.innerHTML = ''; 

                    eventsData.forEach(item => {
                        const localStatus = item.local_status || 'Pendente';
                        const apiStatus = item.status || 'Futuro';
                        const seriesTitle = item.title || 'Série Desconhecida';
                        const tmdbId = item.tmdb_id || '';
                        const seasonNumber = item.season || item.season_number || 1;
                        const episodeNumber = item.number || item.episode_number || 1;
                        const t = parseInt(item.type ?? 0, 10);
                        const contentType = t === 3 ? 'anime' : 'series';
                        const isSynced = localStatus === 'Sincronizado';
                        
                        const eventHtml = `
                            <div class="episode-item ${contentType} ${isSynced ? 'local-synced' : 'local-pending'} ${apiStatus === 'Atualizado' ? 'api-updated' : 'api-late'} js-modal-item" data-tmdb-id="${tmdbId}" data-type="tv">
                                <div class="episode-header">
                                    <div class="episode-status-group">
                                        <span class="status-badge ${apiStatus === 'Atualizado' ? 'status-api-updated' : 'status-api-late'}">
                                            API: ${apiStatus === 'Atualizado' ? 'Atualizado' : (apiStatus || 'Desconhecido')}
                                        </span>
                                        <span class="status-badge ${isSynced ? 'status-local-synced' : 'status-local-pending'} status-local">
                                            Local: ${isSynced ? 'Sincronizado' : 'Pendente'}
                                        </span>
                                    </div>
                                </div>
                                <div class="episode-title">${seriesTitle}</div>
                                <div class="episode-number">T${seasonNumber}E${String(episodeNumber).padStart(2, '0')}</div>
                                <div class="episode-actions">
                                    <button class="sync-single-button js-sync-single-item" title="Sincronizar Série">
                                        <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0011.664 0l3.18-3.185" />
                                        </svg>
                                        Sincronizar Série
                                    </button>
                                </div>
                            </div>
                        `;
                        modalBody.insertAdjacentHTML('beforeend', eventHtml);
                    });
                    
                    modal.classList.add('show');
                };
                
                const closeModal = () => {
                    if (modal) modal.classList.remove('show');
                };

                if (modalClose) {
                    modalClose.addEventListener('click', closeModal);
                }
                
                if (modal) {
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) closeModal();
                    });
                }
                
                document.addEventListener('keydown', (e) => {
                    if (e.key === "Escape" && modal && modal.classList.contains('show')) closeModal();
                });

                // Atualiza a UI para itens sincronizados por TMDB ID
                function markTmdbAsSynced(tmdbId) {
                    // Atualiza badges no modal e em day-view
                    document.querySelectorAll(`.js-modal-item[data-tmdb-id="${tmdbId}"] .status-local`).forEach(badge => {
                        badge.textContent = 'Local: Sincronizado';
                        badge.classList.remove('status-local-pending');
                        badge.classList.add('status-local-synced');
                        const box = badge.closest('.js-modal-item');
                        if (box) {
                            box.classList.remove('local-pending');
                            box.classList.add('local-synced');
                        }
                    });

                    // Atualiza dataset.events em todas as células do calendário
                    document.querySelectorAll('.calendar-day-cell.has-events').forEach(cell => {
                        const events = parseEventsFromCell(cell);
                        let changed = false;
                        const updated = events.map(ev => {
                            if ((ev.tmdb_id || '').toString() === tmdbId.toString()) {
                                if (ev.local_status !== 'Sincronizado') {
                                    ev.local_status = 'Sincronizado';
                                    changed = true;
                                }
                            }
                            return ev;
                        });
                        if (changed) {
                            writeEventsToCell(cell, updated);
                            // Atualiza flags para filtros
                            const hasPending = updated.some(i => i.local_status === 'Pendente');
                            const hasSynced = updated.some(i => i.local_status === 'Sincronizado');
                            cell.dataset.filterPending = hasPending ? 'true' : 'false';
                            cell.dataset.filterSynced = hasSynced ? 'true' : 'false';
                        }
                    });

                    reapplyActiveFilter();
                }

                // Ações por item (modal e day view) — sempre chama tmdb.store para sincronizar a série
                function attachActionHandlers(container) {
                    if (!container) return;
                    container.addEventListener('click', function(event) {
                        const syncButton = event.target.closest('.js-sync-single-item');
                        if (syncButton) {
                            event.preventDefault();
                            const itemContainer = syncButton.closest('.js-modal-item');
                            const tmdbId = itemContainer?.dataset.tmdbId;
                            const type = itemContainer?.dataset.type || 'tv';
                            if (!tmdbId) return;

                            syncButton.disabled = true;
                            const originalContent = syncButton.innerHTML;
                            syncButton.innerHTML = '<div class="loading-spinner"></div> Sincronizando...';

                            const formData = new FormData();
                            formData.append('_token', '{{ csrf_token() }}');
                            formData.append('type', type);
                            formData.append('tmdb_id', tmdbId);

                            fetch("{{ route('admin.tmdb.store') }}", {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(async (response) => {
                                let body = {};
                                try { body = await response.json(); } catch {}
                                if (response.status === 200 || response.status === 208) {
                                    markTmdbAsSynced(tmdbId);
                                    alert(body.message || 'Sincronização concluída.');
                                } else {
                                    alert(body.message || 'Falha na sincronização.');
                                }
                            })
                            .catch(() => alert('Erro de comunicação com o servidor.'))
                            .finally(() => {
                                syncButton.disabled = false;
                                syncButton.innerHTML = originalContent;
                            });
                        }
                    });
                }

                attachActionHandlers(modalBody);
                attachActionHandlers(dayViewEl);

                // Sincronização em massa (100% client-side usando tmdb.store por TMDB ID)
                const syncButtons = document.querySelectorAll('[data-sync-type]');
                const retryButton = document.getElementById('retry-sync-button');
                const progressContainer = document.getElementById('sync-progress');
                const progressBar = document.getElementById('sync-progress-bar');
                const progressStats = document.getElementById('progress-stats');
                const createdCountEl = document.getElementById('created-count');
                const updatedCountEl = document.getElementById('updated-count');
                const skippedCountEl = document.getElementById('sync-skipped-count');
                const failedCountEl = document.getElementById('sync-failed-count');

                let lastSyncType = 'all';
                let isBulkSyncRunning = false;

                function gatherCalendarTmdbIds(syncType) {
                    // syncType: 'all' | 'series' | 'anime'
                    const cells = document.querySelectorAll('.calendar-day-cell.has-events');
                    const idsSet = new Set();
                    let debugInfo = { totalEvents: 0, filteredEvents: 0, updatedEvents: 0 };

                    cells.forEach(cell => {
                        const events = parseEventsFromCell(cell);
                        debugInfo.totalEvents += events.length;
                        
                        events.forEach(ev => {
                            const t = parseInt(ev.type ?? 0, 10);
                            
                            // Apply content type filter first
                            if (syncType === 'series' && t !== 2) return;
                            if (syncType === 'anime' && t !== 3) return;
                            
                            debugInfo.filteredEvents++;
                            
                            // Sincronizamos apenas itens com status da API "Atualizado"
                            if ((ev.status || '') !== 'Atualizado') return;
                            
                            debugInfo.updatedEvents++;
                            
                            const tmdbId = ev.tmdb_id;
                            if (!tmdbId) return;
                            idsSet.add(String(tmdbId));
                        });
                    });

                    console.log(`Coleta de IDs para sincronização (${syncType}):`, debugInfo);
                    console.log(`Total de séries únicas encontradas: ${idsSet.size}`);
                    
                    return Array.from(idsSet.values());
                }

                function showProgress() {
                    if (progressContainer) {
                        progressContainer.style.display = 'block';
                        updateProgress(0, 0, 0, 0, 0, 0);
                    }
                }

                function hideProgress() {
                    if (progressContainer) {
                        progressContainer.style.display = 'none';
                    }
                }

                function updateProgress(processed, total, created, updated, skipped, failed) {
                    if (progressBar) {
                        const pct = total > 0 ? Math.round((processed / total) * 100) : 0;
                        progressBar.style.width = pct + '%';
                    }
                    if (progressStats) {
                        progressStats.textContent = `${processed}/${total} processados (itens únicos da API com status "Atualizado")`;
                    }
                    if (createdCountEl) createdCountEl.textContent = created;
                    if (updatedCountEl) updatedCountEl.textContent = updated;
                    if (skippedCountEl) skippedCountEl.textContent = skipped;
                    if (failedCountEl) failedCountEl.textContent = failed;
                }

                function toggleSyncButtons(enabled) {
                    syncButtons.forEach(button => {
                        button.disabled = !enabled;
                        if (!enabled) {
                            button.dataset.originalText = button.innerHTML;
                            button.innerHTML = '<div class="loading-spinner"></div> Sincronizando...';
                        } else if (button.dataset.originalText) {
                            button.innerHTML = button.dataset.originalText;
                            delete button.dataset.originalText;
                        }
                    });
                }

                async function runClientSideSync(syncType) {
                    if (isBulkSyncRunning) return;

                    isBulkSyncRunning = true;
                    lastSyncType = syncType;
                    toggleSyncButtons(false);
                    showProgress();

                    const tmdbIds = gatherCalendarTmdbIds(syncType);
                    const total = tmdbIds.length;

                    if (total === 0) {
                        updateProgress(0, 0, 0, 0, 0, 0);
                        const progressTitle = document.getElementById('progress-title');
                        if (progressTitle) {
                            progressTitle.textContent = 'Nenhum item encontrado para sincronizar';
                        }
                        alert('Nenhum item "Atualizado" encontrado para este filtro.');
                        toggleSyncButtons(true);
                        hideProgress();
                        isBulkSyncRunning = false;
                        return;
                    }

                    // Update progress title to be more informative
                    const progressTitle = document.getElementById('progress-title');
                    if (progressTitle) {
                        let typeText = '';
                        if (syncType === 'series') typeText = ' (séries)';
                        else if (syncType === 'anime') typeText = ' (animes)';
                        progressTitle.textContent = `Sincronizando ${total} itens da API${typeText}...`;
                    }

                    let processed = 0, created = 0, updated = 0, skipped = 0, failed = 0;

                    for (const tmdbId of tmdbIds) {
                        const formData = new FormData();
                        formData.append('_token', '{{ csrf_token() }}');
                        formData.append('type', 'tv');
                        formData.append('tmdb_id', tmdbId);

                        try {
                            const response = await fetch("{{ route('admin.tmdb.store') }}", {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            });

                            let body = {};
                            try { body = await response.json(); } catch {}

                            if (response.status === 200) {
                                // Decide created vs updated pela mensagem
                                const msg = (body.message || '').toLowerCase();
                                if (msg.includes('updated')) updated++;
                                else created++;

                                // Atualiza DOM para este tmdbId
                                markTmdbAsSynced(tmdbId);
                            } else if (response.status === 208) {
                                skipped++;
                                markTmdbAsSynced(tmdbId);
                            } else {
                                failed++;
                            }
                        } catch (e) {
                            failed++;
                        } finally {
                            processed++;
                            updateProgress(processed, total, created, updated, skipped, failed);
                        }
                    }

                    toggleSyncButtons(true);
                    isBulkSyncRunning = false;

                    alert(`Sincronização concluída.\nCriados: ${created} | Atualizados: ${updated} | Ignorados: ${skipped} | Falhas: ${failed}`);
                    // Opcional: recarregar para refletir tudo
                    // setTimeout(() => window.location.reload(), 1500);
                }

                syncButtons.forEach(button => {
                    button.addEventListener('click', () => runClientSideSync(button.dataset.syncType));
                });

                if (retryButton) {
                    retryButton.addEventListener('click', () => runClientSideSync(lastSyncType || 'all'));
                }

                // Bulk Import (existente)
                document.querySelectorAll('.ajax-form').forEach(form => {
                    form.addEventListener('submit', function(event) {
                        event.preventDefault();
                        const button = form.querySelector('button, input[type="submit"]');
                        button.disabled = true;
                        button.textContent = '...';
                        const postdata = new FormData(form);
                        const formurl = form.getAttribute('action');
                        const dataId = form.getAttribute('data-id');
                        fetch(formurl, {
                            method: 'POST',
                            body: postdata,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        })
                        .then(response => {
                            if (response.ok) {
                                document.querySelector('.form' + dataId)?.remove();
                            } else {
                                alert('Erro ao importar.');
                                button.disabled = false;
                                button.textContent = 'Import';
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            button.disabled = false;
                            button.textContent = 'Import';
                        });
                    });
                });
                
                const startButton = document.getElementById('start-bulk-import');
                const bulkImportForm = document.getElementById('bulk-import-form');
                const bulkStatusDiv = document.getElementById('bulk-import-status');
                const newImportButton = document.getElementById('new-import-button');
                
                if (startButton) {
                    startButton.addEventListener('click', async () => {
                        const type = document.getElementById('bulk_type').value;
                        const idsText = document.getElementById('bulk_ids').value;
                        const ids = idsText.split(/[\s,]+/).filter(id => id.trim() !== '');
                        if (ids.length === 0) {
                            alert('Por favor, insira os IDs.');
                            return;
                        }
                        bulkImportForm.classList.add('hidden');
                        bulkStatusDiv.classList.remove('hidden');
                        newImportButton.classList.add('hidden');
                        document.getElementById('job-details').innerHTML = '';
                        let successCount = 0, skippedCount = 0, failedCount = 0;
                        const totalJobs = ids.length;
                        const updateOverallProgress = () => {
                            const processedCount = successCount + skippedCount + failedCount;
                            const percentage = totalJobs > 0 ? (processedCount / totalJobs) * 100 : 0;
                            document.getElementById('progress-bar').style.width = percentage + '%';
                            document.getElementById('progress-text').textContent = `Processando ${processedCount} de ${totalJobs}...`;
                            document.getElementById('success-count').textContent = successCount;
                            document.getElementById('skipped-count').textContent = skippedCount;
                            document.getElementById('failed-count').textContent = failedCount;
                        };
                        updateOverallProgress();
                        for (const id of ids) {
                            const jobElement = document.createElement('div');
                            jobElement.className = 'text-sm p-2 rounded-md bg-gray-50 dark:bg-gray-800';
                            jobElement.innerHTML = `<div class="flex justify-between items-center"><span class="font-semibold text-gray-800 dark:text-gray-200">ID: ${id}</span><span class="job-status font-bold text-blue-500">⚙ Processando...</span></div><p class="job-message text-xs text-gray-500 dark:text-gray-400 mt-1"></p>`;
                            document.getElementById('job-details').prepend(jobElement);
                            const jobStatusSpan = jobElement.querySelector('.job-status');
                            const jobMessageP = jobElement.querySelector('.job-message');
                            const formData = new FormData();
                            formData.append('_token', '{{ csrf_token() }}');
                            formData.append('type', type);
                            formData.append('tmdb_id', id.trim());
                            try {
                                const response = await fetch("{{ route('admin.tmdb.store') }}", {
                                    method: 'POST',
                                    body: formData,
                                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                                });
                                const result = await response.json();
                                if (response.status === 200) {
                                    successCount++;
                                    jobStatusSpan.className = 'job-status font-bold text-green-500';
                                    jobStatusSpan.textContent = '✓ Sucesso';
                                    jobMessageP.textContent = result.message;
                                } else if (response.status === 208) {
                                    skippedCount++;
                                    jobStatusSpan.className = 'job-status font-bold text-orange-500';
                                    jobStatusSpan.textContent = '✓ Ignorado';
                                    jobMessageP.textContent = result.message;
                                } else {
                                    failedCount++;
                                    jobStatusSpan.className = 'job-status font-bold text-red-500';
                                    jobStatusSpan.textContent = '✗ Falha';
                                    jobMessageP.textContent = result.message || 'Erro desconhecido.';
                                }
                            } catch (error) {
                                failedCount++;
                                jobStatusSpan.className = 'job-status font-bold text-red-500';
                                jobStatusSpan.textContent = '✗ Falha';
                                jobMessageP.textContent = 'Erro de rede ou resposta inválida do servidor.';
                            }
                            updateOverallProgress();
                        }
                        document.getElementById('progress-text').textContent = 'Importação Concluída!';
                        newImportButton.classList.remove('hidden');
                    });
                }
                
                if (newImportButton) {
                    newImportButton.addEventListener('click', () => {
                        bulkStatusDiv.classList.add('hidden');
                        bulkImportForm.classList.remove('hidden');
                        document.getElementById('bulk_ids').value = '';
                    });
                }
            });
        </script>
    @endpush
@endsection