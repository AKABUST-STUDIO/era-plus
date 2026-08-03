@props(['expense'])

<livewire:finance.reimbursement
    :expense="$expense"
    :key="'reimbursement-' . $expense->getKey()"
/>
