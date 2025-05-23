# Business Manager - SnipeDeal

Questo documento descrive la funzionalità Business Manager di SnipeDeal, progettata per gestire e tracciare gli affari/deal generati dalle campagne.

## Obiettivo

Il Business Manager permette agli utenti di:
1. Trasformare i risultati delle campagne in opportunità di business tracciabili
2. Gestire l'intero ciclo di vita di un affare (dall'acquisizione alla vendita)
3. Calcolare automaticamente profitti e margini
4. Visualizzare metriche aggregate sul rendimento delle attività

## Schema del Database

### Nuova Tabella: `deals`

```sql
CREATE TABLE `deals` (
  `id` bigint UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint UNSIGNED NOT NULL,
  `campaign_id` bigint UNSIGNED NULL,
  `campaign_result_id` bigint UNSIGNED NULL,
  `date` date NOT NULL,
  `product` varchar(255) NOT NULL,
  `sku` varchar(100) NULL,
  `link` varchar(255) NULL,
  `contact` varchar(100) NULL,
  `sale_amount` decimal(10,2) NULL,
  `product_cost` decimal(10,2) NOT NULL,
  `advertising_cost` decimal(10,2) NULL DEFAULT '0.00',
  `shipping_cost` decimal(10,2) NULL DEFAULT '0.00',
  `other_costs` decimal(10,2) NULL DEFAULT '0.00',
  `status` enum('in_stock','sold') NOT NULL DEFAULT 'in_stock',
  `notes` text NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deals_user_id_foreign` (`user_id`),
  KEY `deals_campaign_id_foreign` (`campaign_id`),
  KEY `deals_campaign_result_id_foreign` (`campaign_result_id`),
  CONSTRAINT `deals_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deals_campaign_id_foreign` FOREIGN KEY (`campaign_id`) REFERENCES `campaigns` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deals_campaign_result_id_foreign` FOREIGN KEY (`campaign_result_id`) REFERENCES `campaign_results` (`id`) ON DELETE SET NULL
);
```

### Campi Calcolati (non memorizzati nel database)
- `profit`: `sale_amount - (product_cost + advertising_cost + shipping_cost + other_costs)`
- `margin_percentage`: `(profit / sale_amount) * 100`

## Interfaccia Utente

### Layout Principale
La pagina Business Manager è composta da:

1. **Dashboard in alto**
   - Card: Valore in Stock (somma del costo dei prodotti in stato "in_stock")
   - Card: Valore Venduto (somma degli importi di vendita in stato "sold")
   - Card: Profitto Totale (differenza tra importo vendite e costi totali)

2. **Filtri di Ricerca**
   - Filtro per Periodo (data)
   - Filtro per Campagna
   - Filtro per Stato (in_stock, sold)

3. **Tabella dei Deal**
   - Colonne per tutti i campi elencati sopra
   - Colonne aggiuntive per Profitto e Margine % (calcolati dinamicamente)
   - Azioni: Modifica, Elimina, Marca come Venduto

4. **Form Creazione/Modifica**
   - Tutti i campi della tabella `deals`
   - Opzione per selezionare una campagna e un risultato esistente
   - Calcolo automatico di Profitto e Margine % durante la compilazione

### Flussi Utente

#### Creazione Deal da Risultato Campagna
1. Utente visualizza i risultati di una campagna
2. Clicca "Converti in Deal" su un risultato
3. Si apre il form con i campi pre-compilati dal risultato della campagna:
   - Link annuncio
   - Prodotto (dal titolo)
   - Data (attuale)
   - Campagna (collegamento automatico)
4. Utente completa i campi rimanenti
5. Salva il deal

#### Creazione Deal Manuale
1. Utente accede a Business Manager
2. Clicca "Nuovo Deal"
3. Compila tutti i campi richiesti
4. Salva il deal

#### Aggiornamento Stato
1. Utente trova un deal nella tabella
2. Clicca "Marca come Venduto"
3. Si apre un dialog per inserire l'importo di vendita
4. Conferma e il deal viene aggiornato

## Comportamenti e Logica di Business

### Calcoli Automatici
- Il profitto viene calcolato come: `sale_amount - (product_cost + advertising_cost + shipping_cost + other_costs)`
- Il margine percentuale viene calcolato come: `(profit / sale_amount) * 100`
- I deal in stato "in_stock" hanno `sale_amount` nullo e quindi profitto e margine non calcolabili

### Regole di Validazione
- `product_cost` è obbligatorio
- `sale_amount` è obbligatorio solo per i deal in stato "sold"
- `date` non può essere nel futuro
- Se collegato a un risultato di campagna, i campi `campaign_id` e `campaign_result_id` devono essere consistenti

### Integrazione con Campagne
- Un deal può essere collegato a una campagna e a un risultato specifico
- Questa relazione è opzionale (i deal possono essere creati manualmente)
- Quando un risultato di campagna viene convertito in deal, viene mantenuto il collegamento per la tracciabilità

## Modelli e Relazioni

```php
// Deal.php
class Deal extends Model
{
    protected $fillable = [
        'user_id', 'campaign_id', 'campaign_result_id', 'date', 'product',
        'sku', 'link', 'contact', 'sale_amount', 'product_cost', 
        'advertising_cost', 'shipping_cost', 'other_costs', 'status', 'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'sale_amount' => 'decimal:2',
        'product_cost' => 'decimal:2',
        'advertising_cost' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'status' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function campaignResult()
    {
        return $this->belongsTo(CampaignResult::class);
    }

    // Attributi calcolati
    public function getProfitAttribute()
    {
        if ($this->status !== 'sold' || !$this->sale_amount) {
            return null;
        }
        
        return $this->sale_amount - (
            $this->product_cost + 
            $this->advertising_cost + 
            $this->shipping_cost + 
            $this->other_costs
        );
    }

    public function getMarginPercentageAttribute()
    {
        if (!$this->profit || !$this->sale_amount) {
            return null;
        }
        
        return ($this->profit / $this->sale_amount) * 100;
    }

    public function getTotalCostAttribute()
    {
        return $this->product_cost + 
               $this->advertising_cost + 
               $this->shipping_cost + 
               $this->other_costs;
    }
}
```

## Controller e Routes

### Routes

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/business', [BusinessManagerController::class, 'index'])
         ->name('business.index');
    
    Route::get('/business/create', [BusinessManagerController::class, 'create'])
         ->name('business.create');
    
    Route::post('/business', [BusinessManagerController::class, 'store'])
         ->name('business.store');
    
    Route::get('/business/{deal}/edit', [BusinessManagerController::class, 'edit'])
         ->name('business.edit');
    
    Route::put('/business/{deal}', [BusinessManagerController::class, 'update'])
         ->name('business.update');
    
    Route::delete('/business/{deal}', [BusinessManagerController::class, 'destroy'])
         ->name('business.destroy');
    
    Route::put('/business/{deal}/mark-as-sold', [BusinessManagerController::class, 'markAsSold'])
         ->name('business.markAsSold');
    
    Route::post('/business/from-campaign-result/{result}', [BusinessManagerController::class, 'createFromResult'])
         ->name('business.createFromResult');
});
```

## Dashboard Metrics

### Dati da Esporre
1. **Valore in Stock**: 
   ```php
   Deal::where('user_id', auth()->id())
       ->where('status', 'in_stock')
       ->sum('product_cost');
   ```

2. **Valore Venduto**: 
   ```php
   Deal::where('user_id', auth()->id())
       ->where('status', 'sold')
       ->sum('sale_amount');
   ```

3. **Profitto Totale**: 
   ```php
   $deals = Deal::where('user_id', auth()->id())
       ->where('status', 'sold')
       ->get();
   
   $totalProfit = $deals->sum(function ($deal) {
       return $deal->profit;
   });
   ```

## Implementazione delle Viste

### Layout della Pagina Principale

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Business Manager') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Dashboard Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Valore in Stock</h3>
                        <p class="mt-1 text-3xl font-semibold">€ {{ number_format($stockValue, 2) }}</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Valore Venduto</h3>
                        <p class="mt-1 text-3xl font-semibold">€ {{ number_format($soldValue, 2) }}</p>
                    </div>
                </div>
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 bg-white border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Profitto</h3>
                        <p class="mt-1 text-3xl font-semibold">€ {{ number_format($totalProfit, 2) }}</p>
                    </div>
                </div>
            </div>
            
            <!-- Filters and Actions -->
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between">
                    <div class="flex flex-wrap items-center space-x-4">
                        <!-- Filters -->
                    </div>
                    <a href="{{ route('business.create') }}" 
                       class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-500">
                        Nuovo Deal
                    </a>
                </div>
            </div>
            
            <!-- Deals Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <!-- Table Content -->
            </div>
        </div>
    </div>
</x-app-layout>
```

## Passi per l'Implementazione

1. **Creazione Migrazione**
   - Creare migrazione per la tabella `deals`

2. **Creazione Model**
   - Implementare `Deal.php` con relazioni e attributi calcolati

3. **Creazione Controller**
   - Implementare `BusinessManagerController.php` con tutti i metodi CRUD

4. **Implementazione Viste**
   - `index.blade.php`: Dashboard e tabella
   - `create.blade.php` e `edit.blade.php`: Form di creazione/modifica
   - `_form.blade.php`: Component riutilizzabile per il form

5. **Aggiunta alla Navigazione**
   - Aggiungere link al Business Manager nella barra di navigazione

6. **Implementare Funzionalità "Converti in Deal"**
   - Aggiungere bottone nei risultati delle campagne
   - Implementare logica per pre-compilare il form 