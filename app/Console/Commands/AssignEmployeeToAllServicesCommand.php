<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Console\Command;
use Modules\Employee\Models\BranchEmployee;
use Modules\Service\Models\Service;
use Modules\Service\Models\ServiceEmployee;

class AssignEmployeeToAllServicesCommand extends Command
{
    protected $signature = 'employee:assign-all-services
                            {employee : User id (stručnjak / zaposlenik)}
                            {branch? : Branch id (poslovnica za branch_employee, preporučeno za employee_list API)}
                            {--dry-run : Samo ispis što bi se uradilo}
                            {--ensure-employee-role : Dodeli Spatie ulogu employee ako korisnik nema}';

    protected $description = 'Poveže korisnika s poslovnicom (branch_employee) i dodeli mu sve aktivne usluge (service_employees). Korisno nakon dodavanja novih usluga na produkciju.';

    public function handle(): int
    {
        $employeeId = (int) $this->argument('employee');
        $branchArg = $this->argument('branch');
        $branchId = $branchArg !== null ? (int) $branchArg : null;
        $dry = (bool) $this->option('dry-run');

        $user = User::query()->find($employeeId);
        if (! $user) {
            $this->error("Korisnik id={$employeeId} nije pronađen.");

            return self::FAILURE;
        }

        if ($this->option('ensure-employee-role')) {
            if ($dry) {
                $this->line('[dry-run] Provjera uloge employee…');
            } elseif (! $user->hasRole('employee')) {
                $user->assignRole('employee');
                $this->info('Dodijeljena uloga: employee');
            } else {
                $this->info('Korisnik već ima ulogu employee.');
            }
        } elseif (! $user->hasRole('employee')) {
            $this->warn('Korisnik nema ulogu "employee". Ponovi s --ensure-employee-role ili postavi u adminu.');
        }

        if ($branchId !== null) {
            $branch = Branch::query()->find($branchId);
            if (! $branch) {
                $this->error("Poslovnica id={$branchId} ne postoji.");

                return self::FAILURE;
            }
            if ($dry) {
                $this->line("[dry-run] branch_employee: employee_id={$employeeId}, branch_id={$branchId}");
            } else {
                BranchEmployee::firstOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'branch_id' => $branchId,
                    ],
                    ['is_primary' => 0]
                );
                $this->info("Povezano s poslovnicom branch_id={$branchId} (branch_employee).");
            }
        } else {
            $this->warn('Nije naveden branch: employee_list u API treba i branch_employee. Daj drugi argument branch id ili poveži ručno u adminu.');
        }

        $serviceIds = Service::query()
            ->where('status', 1)
            ->pluck('id');

        if ($serviceIds->isEmpty()) {
            $this->warn('Nema aktivnih usluga (status=1).');

            return self::SUCCESS;
        }

        $created = 0;
        foreach ($serviceIds as $serviceId) {
            if ($dry) {
                $this->line("[dry-run] service_employees: service_id={$serviceId}, employee_id={$employeeId}");
                $created++;
            } else {
                $row = ServiceEmployee::firstOrCreate(
                    [
                        'service_id' => $serviceId,
                        'employee_id' => $employeeId,
                    ],
                    []
                );
                if ($row->wasRecentlyCreated) {
                    $created++;
                }
            }
        }

        if ($dry) {
            $this->info("Dry-run: {$serviceIds->count()} usluga, {$created} bi bilo umetnuto/prikazano.");
        } else {
            $this->info("Goto: {$serviceIds->count()} aktivnih usluga; novih service_employees redova: {$created}.");
        }
        $this->comment('Provjera u appu: odaberi istu poslovnicu i uslugu, popis stručnjaka treba sadržavati ovog korisnika (employee_list).');

        return self::SUCCESS;
    }
}
