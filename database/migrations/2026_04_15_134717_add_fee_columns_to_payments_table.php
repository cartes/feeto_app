<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            // amount ya existe (monto bruto que paga el cliente)
            // Separamos comisión MP en neto + IVA (Chile: IVA 19%)
            $table->unsignedInteger('mp_fee_net')->default(0)->after('amount')
                ->comment('Comisión MP sin IVA');
            $table->unsignedInteger('mp_fee_vat')->default(0)->after('mp_fee_net')
                ->comment('IVA 19% sobre comisión MP');
            $table->unsignedInteger('net_amount')->default(0)->after('mp_fee_vat')
                ->comment('Monto neto recibido después de comisiones');
            $table->string('mp_payment_type')->nullable()->after('mp_payment_id')
                ->comment('credit_card, debit_card, account_money, etc.');
            $table->string('mp_installments')->nullable()->after('mp_payment_type')
                ->comment('Número de cuotas');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['mp_fee_net', 'mp_fee_vat', 'net_amount', 'mp_payment_type', 'mp_installments']);
        });
    }
};
