<!-- Form per la creazione/modifica di un deal -->
<div class="space-y-6">
    <!-- Dati Principali -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Informazioni Principali</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Inserisci le informazioni di base del deal.
                </p>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="grid grid-cols-6 gap-6">
                    <!-- Data -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="date" class="block text-sm font-medium text-gray-700">Data</label>
                        <input type="date" name="date" id="date" value="{{ old('date', $deal->date ?? now()->format('Y-m-d')) }}" 
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                        @error('date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Prodotto -->
                    <div class="col-span-6">
                        <label for="product" class="block text-sm font-medium text-gray-700">Prodotto</label>
                        <input type="text" name="product" id="product" value="{{ old('product', $deal->product ?? '') }}" 
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" required>
                        @error('product')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SKU -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="sku" class="block text-sm font-medium text-gray-700">SKU</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $deal->sku ?? '') }}" 
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        @error('sku')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Link Annuncio -->
                    <div class="col-span-6">
                        <label for="link" class="block text-sm font-medium text-gray-700">Link Annuncio</label>
                        <input type="url" name="link" id="link" value="{{ old('link', $deal->link ?? '') }}" 
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        @error('link')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Contatto -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="contact" class="block text-sm font-medium text-gray-700">Contatto</label>
                        <input type="text" name="contact" id="contact" value="{{ old('contact', $deal->contact ?? '') }}" 
                               class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md">
                        @error('contact')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Stato -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="status" class="block text-sm font-medium text-gray-700">Stato</label>
                        <select id="status" name="status" 
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="in_stock" {{ old('status', $deal->status ?? 'in_stock') == 'in_stock' ? 'selected' : '' }}>In Stock</option>
                            <option value="sold" {{ old('status', $deal->status ?? '') == 'sold' ? 'selected' : '' }}>Venduto</option>
                        </select>
                        @error('status')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dati Campagna -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Dati Campagna</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Associa il deal a una campagna esistente.
                </p>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="grid grid-cols-6 gap-6">
                    <!-- Campagna -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="campaign_id" class="block text-sm font-medium text-gray-700">Campagna</label>
                        <select id="campaign_id" name="campaign_id" 
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Nessuna campagna</option>
                            @foreach($campaigns as $campaign)
                                <option value="{{ $campaign->id }}" {{ old('campaign_id', $deal->campaign_id ?? '') == $campaign->id ? 'selected' : '' }}>
                                    {{ $campaign->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('campaign_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Risultato Campagna -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="campaign_result_id" class="block text-sm font-medium text-gray-700">Risultato Campagna</label>
                        <select id="campaign_result_id" name="campaign_result_id" 
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            <option value="">Nessun risultato</option>
                            @if(isset($campaignResults) && count($campaignResults) > 0)
                                @foreach($campaignResults as $result)
                                    <option value="{{ $result->id }}" {{ old('campaign_result_id', $deal->campaign_result_id ?? '') == $result->id ? 'selected' : '' }}>
                                        {{ $result->title }} - {{ $result->price }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        @error('campaign_result_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dati Economici -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Dati Economici</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Inserisci i dati economici del deal.
                </p>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="grid grid-cols-6 gap-6">
                    <!-- Costo Prodotto -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="product_cost" class="block text-sm font-medium text-gray-700">Costo Prodotto</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">€</span>
                            </div>
                            <input type="number" step="0.01" min="0" name="product_cost" id="product_cost" 
                                   value="{{ old('product_cost', $deal->product_cost ?? '0.00') }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="0.00" required>
                        </div>
                        @error('product_cost')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Importo Vendita -->
                    <div class="col-span-6 sm:col-span-3" id="sale_amount_container">
                        <label for="sale_amount" class="block text-sm font-medium text-gray-700">Importo Vendita</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">€</span>
                            </div>
                            <input type="number" step="0.01" min="0" name="sale_amount" id="sale_amount" 
                                   value="{{ old('sale_amount', $deal->sale_amount ?? '') }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="0.00">
                        </div>
                        @error('sale_amount')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Costo Pubblicità -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="advertising_cost" class="block text-sm font-medium text-gray-700">Costo Pubblicità</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">€</span>
                            </div>
                            <input type="number" step="0.01" min="0" name="advertising_cost" id="advertising_cost" 
                                   value="{{ old('advertising_cost', $deal->advertising_cost ?? '0.00') }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="0.00">
                        </div>
                        @error('advertising_cost')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Costo Spedizione -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="shipping_cost" class="block text-sm font-medium text-gray-700">Costo Spedizione</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">€</span>
                            </div>
                            <input type="number" step="0.01" min="0" name="shipping_cost" id="shipping_cost" 
                                   value="{{ old('shipping_cost', $deal->shipping_cost ?? '0.00') }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="0.00">
                        </div>
                        @error('shipping_cost')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Altri Costi -->
                    <div class="col-span-6 sm:col-span-3">
                        <label for="other_costs" class="block text-sm font-medium text-gray-700">Altri Costi</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">€</span>
                            </div>
                            <input type="number" step="0.01" min="0" name="other_costs" id="other_costs" 
                                   value="{{ old('other_costs', $deal->other_costs ?? '0.00') }}"
                                   class="focus:ring-indigo-500 focus:border-indigo-500 block w-full pl-7 pr-12 sm:text-sm border-gray-300 rounded-md" 
                                   placeholder="0.00">
                        </div>
                        @error('other_costs')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Calcolo Profitto e Margine (solo visualizzazione) -->
                    <div class="col-span-6 sm:col-span-3" id="profit_container">
                        <label class="block text-sm font-medium text-gray-700">Profitto Stimato</label>
                        <div class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-100 rounded-md shadow-sm">
                            <span id="profit_display">€ 0,00</span>
                        </div>
                    </div>

                    <div class="col-span-6 sm:col-span-3" id="margin_container">
                        <label class="block text-sm font-medium text-gray-700">Margine Stimato</label>
                        <div class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-gray-100 rounded-md shadow-sm">
                            <span id="margin_display">0,00%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Note -->
    <div class="bg-white shadow px-4 py-5 sm:rounded-lg sm:p-6">
        <div class="md:grid md:grid-cols-3 md:gap-6">
            <div class="md:col-span-1">
                <h3 class="text-lg font-medium leading-6 text-gray-900">Note</h3>
                <p class="mt-1 text-sm text-gray-500">
                    Aggiungi note o informazioni aggiuntive.
                </p>
            </div>
            <div class="mt-5 md:mt-0 md:col-span-2">
                <div class="grid grid-cols-6 gap-6">
                    <div class="col-span-6">
                        <label for="notes" class="block text-sm font-medium text-gray-700">Note</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 mt-1 block w-full sm:text-sm border border-gray-300 rounded-md">{{ old('notes', $deal->notes ?? '') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pulsanti -->
    <div class="flex justify-end">
        <a href="{{ route('business.index') }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Annulla
        </a>
        <button type="submit" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            Salva
        </button>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Aggiorna i risultati della campagna quando cambia la campagna selezionata
        const campaignSelect = document.getElementById('campaign_id');
        const resultSelect = document.getElementById('campaign_result_id');
        
        campaignSelect.addEventListener('change', function() {
            const campaignId = this.value;
            resultSelect.innerHTML = '<option value="">Nessun risultato</option>';
            
            if (campaignId) {
                // Qui dovresti fare una chiamata AJAX per ottenere i risultati della campagna selezionata
                // Per ora lasciamo vuoto, andrebbe implementato in seguito
            }
        });
        
        // Gestisci la visibilità del campo importo vendita in base allo stato
        const statusSelect = document.getElementById('status');
        const saleAmountContainer = document.getElementById('sale_amount_container');
        const saleAmountInput = document.getElementById('sale_amount');
        
        function updateSaleAmountVisibility() {
            if (statusSelect.value === 'sold') {
                saleAmountContainer.classList.remove('hidden');
                saleAmountInput.setAttribute('required', 'required');
            } else {
                saleAmountInput.removeAttribute('required');
            }
        }
        
        statusSelect.addEventListener('change', updateSaleAmountVisibility);
        updateSaleAmountVisibility();
        
        // Calcola profitto e margine in tempo reale
        const productCostInput = document.getElementById('product_cost');
        const advertisingCostInput = document.getElementById('advertising_cost');
        const shippingCostInput = document.getElementById('shipping_cost');
        const otherCostsInput = document.getElementById('other_costs');
        const profitDisplay = document.getElementById('profit_display');
        const marginDisplay = document.getElementById('margin_display');
        const profitContainer = document.getElementById('profit_container');
        const marginContainer = document.getElementById('margin_container');
        
        function updateProfitAndMargin() {
            const productCost = parseFloat(productCostInput.value) || 0;
            const advertisingCost = parseFloat(advertisingCostInput.value) || 0;
            const shippingCost = parseFloat(shippingCostInput.value) || 0;
            const otherCosts = parseFloat(otherCostsInput.value) || 0;
            const saleAmount = parseFloat(saleAmountInput.value) || 0;
            
            const totalCost = productCost + advertisingCost + shippingCost + otherCosts;
            
            if (statusSelect.value === 'sold' && saleAmount > 0) {
                const profit = saleAmount - totalCost;
                const margin = (profit / saleAmount) * 100;
                
                profitDisplay.textContent = '€ ' + profit.toFixed(2).replace('.', ',');
                marginDisplay.textContent = margin.toFixed(2).replace('.', ',') + '%';
                
                profitDisplay.className = profit >= 0 ? 'text-green-600' : 'text-red-600';
                marginDisplay.className = margin >= 0 ? 'text-green-600' : 'text-red-600';
                
                profitContainer.classList.remove('hidden');
                marginContainer.classList.remove('hidden');
            } else {
                profitDisplay.textContent = '€ 0,00';
                marginDisplay.textContent = '0,00%';
                
                if (statusSelect.value !== 'sold') {
                    profitContainer.classList.add('hidden');
                    marginContainer.classList.add('hidden');
                } else {
                    profitContainer.classList.remove('hidden');
                    marginContainer.classList.remove('hidden');
                }
            }
        }
        
        productCostInput.addEventListener('input', updateProfitAndMargin);
        advertisingCostInput.addEventListener('input', updateProfitAndMargin);
        shippingCostInput.addEventListener('input', updateProfitAndMargin);
        otherCostsInput.addEventListener('input', updateProfitAndMargin);
        saleAmountInput.addEventListener('input', updateProfitAndMargin);
        statusSelect.addEventListener('change', updateProfitAndMargin);
        
        // Inizializza i calcoli
        updateProfitAndMargin();
    });
</script>
@endpush 