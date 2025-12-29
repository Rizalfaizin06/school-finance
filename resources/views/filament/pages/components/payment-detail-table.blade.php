<div x-data="{ 
    showPaymentForm: false, 
    selectedMonth: null,
    selectedYear: null,
    selectedMonthNo: null,
    accountId: '',
    paymentMethod: 'cash',
    paymentDate: '{{ date('Y-m-d') }}',
    amount: 150000,
    notes: ''
}">
    @php
        $totalPaid = collect($paymentDetails)->where('is_paid', true)->count();
        $totalShouldPay = collect($paymentDetails)->where('should_pay', true)->count();
        $totalOutstanding = $totalShouldPay - $totalPaid;
    @endphp

    <!-- Student Info as Form Fields -->
    <div class="grid grid-cols-4 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium mb-1">NIS</label>
            <input type="text" value="{{ $student->nis }}" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-800 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Nama Siswa</label>
            <input type="text" value="{{ $student->name }}" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-800 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Kelas</label>
            <input type="text" value="{{ $student->class->name ?? '-' }}" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-800 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Tanggal Masuk</label>
            <input type="text" value="{{ \Carbon\Carbon::parse($student->enrollment_date)->format('d M Y') }}" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-800 text-sm">
        </div>
    </div>

    <!-- Summary Info -->
    <div class="grid grid-cols-3 gap-4 mb-4">
        <div>
            <label class="block text-sm font-medium mb-1">Sudah Dibayar</label>
            <input type="text" value="{{ $totalPaid }} bulan" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-green-50 dark:bg-green-900/20 text-sm font-semibold">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Tunggakan</label>
            <input type="text" value="{{ $totalOutstanding }} bulan" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-red-50 dark:bg-red-900/20 text-sm font-semibold">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Belum Jatuh Tempo</label>
            <input type="text" value="{{ 72 - $totalShouldPay }} bulan" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-blue-50 dark:bg-blue-900/20 text-sm font-semibold">
        </div>
    </div>

    <!-- Payment Table -->
    <div class="border border-gray-300 dark:border-gray-600 rounded-md overflow-hidden">
        <div style="max-height: 450px; overflow-y: auto;">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 dark:bg-gray-800 sticky top-0">
                    <tr>
                        <th class="px-3 py-2 text-left border-b border-gray-300 dark:border-gray-600 font-semibold">No</th>
                        <th class="px-3 py-2 text-left border-b border-gray-300 dark:border-gray-600 font-semibold">Periode</th>
                        <th class="px-3 py-2 text-center border-b border-gray-300 dark:border-gray-600 font-semibold">Status</th>
                        <th class="px-3 py-2 text-left border-b border-gray-300 dark:border-gray-600 font-semibold">Tanggal Bayar</th>
                        <th class="px-3 py-2 text-left border-b border-gray-300 dark:border-gray-600 font-semibold">No. Kwitansi</th>
                        <th class="px-3 py-2 text-right border-b border-gray-300 dark:border-gray-600 font-semibold">Nominal</th>
                        <th class="px-3 py-2 text-center border-b border-gray-300 dark:border-gray-600 font-semibold">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($paymentDetails as $detail)
                        <tr class="border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                            <td class="px-3 py-2">{{ $detail['no'] }}</td>
                            <td class="px-3 py-2">{{ $detail['month_short'] }}</td>
                            <td class="px-3 py-2 text-center">
                                @if($detail['is_paid'])
                                    <span class="px-2 py-1 bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-100 rounded text-xs font-medium">Lunas</span>
                                @elseif($detail['should_pay'])
                                    <span class="px-2 py-1 bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-100 rounded text-xs font-medium">Belum Bayar</span>
                                @else
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded text-xs">Belum Jatuh Tempo</span>
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $detail['payment_date'] ?? '-' }}</td>
                            <td class="px-3 py-2 font-mono text-xs">{{ $detail['receipt_number'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-right font-semibold">{{ $detail['amount'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-center">
                                @if(!$detail['is_paid'] && $detail['should_pay'])
                                    <button 
                                        @click="showPaymentForm = true; selectedMonth = '{{ $detail['month_short'] }}'; selectedYear = {{ $detail['year'] }}; selectedMonthNo = {{ $detail['month'] }}"
                                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-medium">
                                        Bayar
                                    </button>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Form Modal -->
    <div x-show="showPaymentForm" 
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto" 
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div @click="showPaymentForm = false" class="fixed inset-0 bg-black opacity-50"></div>
            
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-semibold mb-4">Form Pembayaran SPP</h3>
                
                <form method="POST" action="{{ route('detail-pembayaran-siswa.process-payment') }}">
                    @csrf
                    <input type="hidden" name="student_id" value="{{ $student->id }}">
                    <input type="hidden" name="month" x-model="selectedMonthNo">
                    <input type="hidden" name="year" x-model="selectedYear">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Periode</label>
                            <input type="text" x-model="selectedMonth" readonly class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-gray-50 dark:bg-gray-700 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Akun Pembayaran <span class="text-red-500">*</span></label>
                            <select name="account_id" x-model="accountId" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm">
                                <option value="">Pilih Akun</option>
                                @php
                                    $accounts = \App\Models\Account::all();
                                @endphp
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Metode Pembayaran <span class="text-red-500">*</span></label>
                            <select name="payment_method" x-model="paymentMethod" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm">
                                <option value="cash">Tunai</option>
                                <option value="transfer">Transfer</option>
                                <option value="check">Cek</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Tanggal Pembayaran <span class="text-red-500">*</span></label>
                            <input type="date" name="payment_date" x-model="paymentDate" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Nominal <span class="text-red-500">*</span></label>
                            <input type="number" name="amount" x-model="amount" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-medium mb-1">Catatan</label>
                            <textarea name="notes" x-model="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-sm"></textarea>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 mt-6">
                        <button type="button" @click="showPaymentForm = false" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm font-medium hover:bg-gray-50 dark:hover:bg-gray-700">
                            Batal
                        </button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium">
                            Simpan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>