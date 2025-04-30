<?php

namespace Database\Seeders;

use App\Domain\Invoice\Models\Invoice;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Models\Service;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $products = Product::all();
        $services = Service::all();

        // Statuts possibles pour les factures
        $statuses = [
            'draft', 'sent', 'paid', 'overdue', 'cancelled',
            'deposited', // Statut obligatoire "déposée" pour la facturation électronique
            'rejected',  // Statut obligatoire "rejetée" pour la facturation électronique
            'received',  // Statut "reçue par la plateforme"
            'approved',  // Statut "approuvée"
            'refunded'   // Statut "remboursée"
        ];

        // Types de factures
        $invoiceTypes = [
            'invoice', 'credit_note', 'deposit', 'prepayment', 'final'
        ];

        // Formats électroniques conformes à la réglementation
        $electronicFormats = [
            'UBL', 'CII', 'Factur-X', null // null pour les factures non électroniques
        ];

        // Référence PDP (Plateformes de Dématérialisation Partenaires)
        $pdpReferences = [
            '0001', '0002', '0003', '0004', '0005', null // null pour les factures non transmises
        ];

        // Créer 100 factures
        for ($i = 1; $i <= 100; $i++) {
            // Sélection aléatoire d'un client
            $customer = $customers->random();

            // Générer une date d'émission dans les 12 derniers mois
            $issueDate = Carbon::now()->subDays(rand(1, 365));

            // Générer une date d'échéance entre 15 et 60 jours après la date d'émission
            $dueDate = (clone $issueDate)->addDays(rand(15, 60));

            // Définir si la facture est électronique (environ 50% pour la démo)
            $isElectronic = (rand(1, 100) > 50);
            $electronicFormat = $isElectronic ? $electronicFormats[array_rand(array_filter($electronicFormats))] : null;
            $pdpReference = $isElectronic ? $pdpReferences[array_rand(array_filter($pdpReferences))] : null;

            // Déterminer le statut en fonction de la date d'échéance et du type électronique
            $status = $statuses[array_rand($statuses)];
            if ($isElectronic && $status === 'sent') {
                $status = 'deposited'; // Les factures électroniques envoyées sont "déposées"
            }
            if ($dueDate < Carbon::now() && $status === 'sent') {
                $status = 'overdue';
            }

            // Générer un numéro de facture cohérent
            $invoiceNumber = 'FACT-' . date('Ym', $issueDate->timestamp) . '-' . str_pad($i, 4, '0', STR_PAD_LEFT);

            // Type de facture
            $invoiceType = $invoiceTypes[array_rand($invoiceTypes)];

            // Créer la facture
            $invoice = Invoice::create([
                'customer_id' => $customer->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $issueDate,
                'due_date' => $dueDate,
                'status' => $status,
                'subtotal' => 0, // Sera calculé à partir des éléments
                'tax_total' => 0, // Sera calculé à partir des éléments
                'total' => 0, // Sera calculé à partir des éléments
                'notes' => "Facture pour " . $customer->company_name,
                'terms' => "Paiement à réception de facture. Pénalité de retard: 3 fois le taux d'intérêt légal.",
                'invoice_type' => $invoiceType,
                'is_electronic' => $isElectronic,
                'electronic_format' => $electronicFormat,
                'pdp_reference' => $pdpReference,
                'payment_date' => $status === 'paid' ? (clone $issueDate)->addDays(rand(1, 30)) : null,
                'payment_method' => $status === 'paid' ? ['bank_transfer', 'card', 'check', 'cash'][array_rand(['bank_transfer', 'card', 'check', 'cash'])] : null,
                'payment_reference' => $status === 'paid' ? 'REF-' . strtoupper(substr(md5(rand()), 0, 8)) : null,
                'created_at' => $issueDate,
                'updated_at' => Carbon::now(),
            ]);

            // Nombre d'éléments de la facture (entre 1 et 5)
            $numberOfItems = rand(1, 5);

            $subtotal = 0;
            $taxTotal = 0;

            for ($j = 1; $j <= $numberOfItems; $j++) {
                // Décider si c'est un produit ou un service (70% produit, 30% service)
                $isProduct = (rand(1, 100) <= 70);

                if ($isProduct && $products->count() > 0) {
                    $product = $products->random();
                    $quantity = rand(1, 10);
                    $unitPrice = $product->price;
                    $taxRate = $product->tax_rate;
                    $description = $product->description;
                    $item_type = 'product';
                    $item_id = $product->id;
                } elseif ($services->count() > 0) {
                    $service = $services->random();
                    $quantity = rand(1, 5);
                    $unitPrice = $service->price;
                    $taxRate = $service->tax_rate;
                    $description = $service->description;
                    $item_type = 'service';
                    $item_id = $service->id;
                } else {
                    // Fallback si pas de produits ou services
                    $quantity = rand(1, 10);
                    $unitPrice = rand(10, 1000) / 10;
                    $taxRate = [5.5, 10, 20][array_rand([5.5, 10, 20])];
                    $description = "Élément facturable #" . $j;
                    $item_type = 'custom';
                    $item_id = null;
                }

                $lineTotal = $quantity * $unitPrice;
                $taxAmount = $lineTotal * ($taxRate / 100);

                $subtotal += $lineTotal;
                $taxTotal += $taxAmount;

                // Créer l'élément de facture
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_type' => $item_type,
                    'item_id' => $item_id,
                    'description' => $description,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'line_total' => $lineTotal,
                ]);
            }

            // Mise à jour des totaux de la facture
            $total = $subtotal + $taxTotal;

            $invoice->update([
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'total' => $total
            ]);

            // Si c'est un avoir, inverser le total
            if ($invoiceType === 'credit_note') {
                $invoice->update([
                    'subtotal' => -$subtotal,
                    'tax_total' => -$taxTotal,
                    'total' => -$total
                ]);
            }
        }
    }
}
