<script>
    import { onMount } from 'svelte';
    
    // Props
    export let getAvailableSlotsUrl = "{{ route('get-available-slots') }}";
    export let bookingData = {};
    export let selectedDate = '';
    export let currentTime = '';
    export let originalDate = '';
    
    // State
    let slots = [];
    let isLoading = false;
    let error = null;
    
    // Fetch available slots
    async function fetchAvailableSlots(date) {
        if (!date || !bookingData?.branch_id || !bookingData?.employee_id || !bookingData?.service_duration) {
            console.error('Missing required parameters for fetching slots');
            return [];
        }
        
        isLoading = true;
        error = null;
        
        try {
            const totalServiceDuration = bookingData.service_duration;
            const url = new URL(getAvailableSlotsUrl);
            url.searchParams.append('date', date);
            url.searchParams.append('branch_id', bookingData.branch_id);
            url.searchParams.append('employee_id', bookingData.employee_id);
            url.searchParams.append('service_duration', totalServiceDuration);
            
            console.log('Fetching slots from:', url.toString());
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.status === 'success' && Array.isArray(data.data)) {
                return data.data;
            }
            return [];
        } catch (err) {
            console.error('Error fetching slots:', err);
            error = 'Failed to load available slots. Please try again.';
            return [];
        } finally {
            isLoading = false;
        }
    }
    
    // Filter past slots for the current date
    function filterPastSlots(slots, date) {
        const today = new Date();
        const todayStr = today.toISOString().split('T')[0];
        
        if (date !== todayStr) return slots;
        
        const now = new Date();
        return slots.filter(slot => {
            if (!slot.value || slot.value === 'No Slot Available') return false;
            const slotDateTime = new Date(slot.value.replace(' ', 'T'));
            return slotDateTime > now;
        });
    }
    
    // Handle date change
    $: if (selectedDate) {
        (async () => {
            const allSlots = await fetchAvailableSlots(selectedDate);
            slots = filterPastSlots(allSlots, selectedDate);
        })();
    }
    
    // Dispatch event when a slot is selected
    function selectSlot(slot) {
        if (slot.disabled || slot.value === 'No Slot Available' || !slot.value) return;
        
        const event = new CustomEvent('slotSelected', {
            detail: {
                label: slot.label,
                value: slot.value
            }
        });
        
        dispatchEvent(event);
    }
    
    // Format the current booking indicator
    function isCurrentSlot(slotValue) {
        return slotValue === currentTime && selectedDate === originalDate;
    }
</script>

<div class="time-slots-container">
    {#if isLoading}
        <div class="loading">Loading available slots...</div>
    {:else if error}
        <div class="error">{error}</div>
    {:else if slots.length === 0}
        <div class="no-slots">No slots available for the selected date</div>
    {:else}
        <ul class="time-slots-list">
            {#each slots as slot (slot.value || slot.label)}
                {#const disabled = slot.disabled || slot.value === 'No Slot Available' || !slot.value}
                {#const current = isCurrentSlot(slot.value)}
                
                <li class:disabled>
                    <button
                        type="button"
                        class="time-slot {current ? 'current' : ''}"
                        disabled={disabled}
                        on:click|preventDefault={() => !disabled && selectSlot(slot)}
                    >
                        {slot.label}
                        {#if current}
                            <span class="current-badge">(Current)</span>
                        {/if}
                    </button>
                </li>
            {/each}
        </ul>
    {/if}
</div>

<style>
    .time-slots-container {
        margin: 1rem 0;
    }
    
    .time-slots-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
        gap: 0.5rem;
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .time-slot {
        display: block;
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #ddd;
        background: #fff;
        color: #333;
        text-align: center;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .time-slot:hover:not(:disabled) {
        background: #f0f0f0;
        border-color: #999;
    }
    
    .time-slot:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        text-decoration: line-through;
    }
    
    .time-slot.current {
        background: #e3f2fd;
        border-color: #2196f3;
        font-weight: bold;
    }
    
    .current-badge {
        font-size: 0.8em;
        opacity: 0.8;
    }
    
    .loading, .error, .no-slots {
        padding: 1rem;
        text-align: center;
        background: #f8f9fa;
        border-radius: 4px;
    }
    
    .error {
        color: #dc3545;
        background: #f8d7da;
    }
</style>
