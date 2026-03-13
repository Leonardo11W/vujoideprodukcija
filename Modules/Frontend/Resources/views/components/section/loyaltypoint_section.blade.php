<div class="loyalty-section">
    <div class="heading-box d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h5 class="mb-0 font-size-21-3">Loyalty Points</h5>
        <a class="btn btn-link font-size-16" href="#" data-bs-toggle="modal" data-bs-target="#loyaltyPointsModal">See how loyalty points works</a>
    </div>
    <x-usepoint_section/>
    <div class="loyalty-box bg-purple d-flex flex-wrap align-items-center justify-content-between gap-1 rounded mb-4">
        <p class="mb-0">Total Points</p>
        <h4 class="m-0 text-success">1000</h4>
    </div>
    <!-- History Section -->
    <div class="history-section">
        <div class="heading-box d-flex align-items-center justify-content-between mb-4">
            <h5 class="title-text mb-0 font-size-21-3">History</h5>
                <div class="flex-shrnik-0">
                    <select class="form-select select2">
                        <option value="1">Sort by</option>
                        <option value="2">Price</option>
                        <option value="3">Date/Time</option>
                    </select>
                </div>
        </div>
        <div class="table-responsive">
            <table class="table rounded mb-0 custom-table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Date And Time</th>
                        <th>Loyalty Points</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>#301</td>
                        <td>15/02/2025, 12:30 PM</td>
                        <td class="text-success">+20</td>
                        <td><span class="badge bg-danger-subtle fw-semibold font-size-12 rounded-2">Debit</span>
                        </td>
                    </tr>
                    <tr>
                        <td>#302</td>
                        <td>05/02/2025, 09:55 AM</td>
                        <td class="text-success">+200</td>
                        <td><span class="badge bg-success-subtle fw-semibold font-size-12 rounded">Credit</span>
                        </td>
                    </tr>
                    <tr>
                        <td>#303</td>
                        <td>23/01/2025, 06:45 PM</td>
                        <td class="text-danger">-40</td>
                        <td><span class="badge bg-danger-subtle fw-semibold font-size-12 rounded">Debit</span>
                        </td>
                    </tr>
                    <tr>
                        <td>#304</td>
                        <td>23/12/2024, 05:00 PM</td>
                        <td class="text-success">+80</td>
                        <td><span class="badge bg-danger-subtle fw-semibold font-size-12 rounded">Debit</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
