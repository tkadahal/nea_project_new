<?php

namespace Database\Seeders;

use App\Models\ProjectActivitySchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProjectActivityScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Clear existing data
            ProjectActivitySchedule::query()->delete();

            // Seed Transmission Line schedules
            $this->seedTransmissionLineSchedules();

            // Seed Substation schedules
            $this->seedSubstationSchedules();

            DB::commit();

            $this->command->info('Project Activity Schedules seeded successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error seeding schedules: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Seed Transmission Line schedules
     */
    private function seedTransmissionLineSchedules(): void
    {
        $schedules = [
            // Phase A - Pre-Construction & Procurement Phase
            [
                'code' => 'A',
                'name' => 'Pre-Construction & Procurement Phase',
                'weightage' => 15.00,
                'level' => 1,
                'sort_order' => 1,
                'children' => [
                    ['code' => 'A.1', 'name' => 'Desk Study Report'],
                    ['code' => 'A.2', 'name' => 'Survey Licensing'],
                    ['code' => 'A.3', 'name' => 'Survey & Soil Investigation'],
                    ['code' => 'A.4', 'name' => 'Tower Spotting & Profiling'],
                    ['code' => 'A.5', 'name' => 'Feasibility Study Report'],
                    ['code' => 'A.6', 'name' => 'Cadastral Survey Report'],
                    ['code' => 'A.7', 'name' => 'Environmental Study (IEE/EIA)'],
                    ['code' => 'A.8', 'name' => 'Construction Licensing'],
                    ['code' => 'A.9', 'name' => 'Cost Estimation and Approval'],
                    ['code' => 'A.10', 'name' => 'Preparation of Bidding Document and Approval'],
                    ['code' => 'A.11', 'name' => 'Land Acquisition'],
                    ['code' => 'A.12', 'name' => 'Forest Clearances'],
                    ['code' => 'A.13', 'name' => 'Tendering & Contractor Selection'],
                ],
            ],

            // Phase B - Detailed Design & Engineering Phase
            [
                'code' => 'B',
                'name' => 'Detailed Design & Engineering Phase',
                'weightage' => 10.00,
                'level' => 1,
                'sort_order' => 2,
                'children' => [
                    [
                        'code' => 'B.1',
                        'name' => 'Civil Design',
                        'children' => [
                            ['code' => 'B.1.1', 'name' => 'Data Validation and Detailed survey & Soil Investigation'],
                            ['code' => 'B.1.2', 'name' => 'Structural Design/Drawings of Towers and manufacturing approval'],
                            ['code' => 'B.1.3', 'name' => 'Tower Foundation Design/Drawings'],
                            ['code' => 'B.1.4', 'name' => 'Stringing Chart'],
                        ],
                    ],
                    [
                        'code' => 'B.2',
                        'name' => 'Electrical Design',
                        'children' => [
                            ['code' => 'B.2.1', 'name' => 'Conductors, OPGW, Earthwire, Insulators'],
                            ['code' => 'B.2.2', 'name' => 'OPGW'],
                            ['code' => 'B.2.3', 'name' => 'Earthwire'],
                            ['code' => 'B.2.4', 'name' => 'Insulators'],
                            ['code' => 'B.2.5', 'name' => 'Design of Hardware and fittings'],
                        ],
                    ],
                ],
            ],

            // Phase C - Construction Phase
            [
                'code' => 'C',
                'name' => 'Construction Phase',
                'weightage' => 65.00,
                'level' => 1,
                'sort_order' => 3,
                'children' => [
                    [
                        'code' => 'C.1',
                        'name' => 'Civil Construction',
                        'children' => [
                            ['code' => 'C.1.1', 'name' => 'Construction of Foundation and Stub Setting'],
                            ['code' => 'C.1.2', 'name' => 'Concreting & Backfilling'],
                        ],
                    ],
                    [
                        'code' => 'C.2',
                        'name' => 'Factory Testing & Dispatch Phase',
                        'children' => [
                            ['code' => 'C.2.1', 'name' => 'Tower Testing & Material FAT'],
                            ['code' => 'C.2.2', 'name' => 'Site Delivery of Tower materials'],
                        ],
                    ],
                    [
                        'code' => 'C.3',
                        'name' => 'Tower Erection & Stringing Operations',
                        'children' => [
                            ['code' => 'C.3.1', 'name' => 'Errection of Tower'],
                            ['code' => 'C.3.2', 'name' => 'Insulator & Hardware Hoisting'],
                            ['code' => 'C.3.3', 'name' => 'Conductor Pulling'],
                            ['code' => 'C.3.4', 'name' => 'Accessories Fitting'],
                        ],
                    ],
                ],
            ],

            // Phase D - Testing, Commissioning & Handover
            [
                'code' => 'D',
                'name' => 'Testing, Commissioning & Handover',
                'weightage' => 10.00,
                'level' => 1,
                'sort_order' => 4,
                'children' => [
                    ['code' => 'D.1', 'name' => 'Earth resistance test prior to OPGW/EW stringing'],
                    ['code' => 'D.2', 'name' => 'Pre-Commissioning Checks'],
                    ['code' => 'D.3', 'name' => 'Charging & Trial Run'],
                    ['code' => 'D.4', 'name' => 'Final Documentation & Handover'],
                ],
            ],
        ];

        $this->insertSchedules($schedules, 'transmission_line');
    }

    /**
     * Seed Substation schedules (abbreviated for brevity - expand as needed)
     */
    private function seedSubstationSchedules(): void
    {
        $schedules = [
            // Phase A
            [
                'code' => 'A',
                'name' => 'Pre-Construction & Procurement Phase',
                'weightage' => 10.00,
                'level' => 1,
                'sort_order' => 1,
                'children' => [
                    ['code' => 'A.1', 'name' => 'Desk Study'],
                    ['code' => 'A.2', 'name' => 'Survey Lincense'],
                    ['code' => 'A.3', 'name' => 'Detail Survey and Soil Investigation'],
                    ['code' => 'A.4', 'name' => 'Feasibility Study Report'],
                    ['code' => 'A.5', 'name' => 'Cadestral Survey Report'],
                    ['code' => 'A.6', 'name' => 'Environmental Study (IEE/EIA)'],
                    ['code' => 'A.7', 'name' => 'Construction Lincese'],
                    ['code' => 'A.8', 'name' => 'Cost Estimation Approval'],
                    ['code' => 'A.9', 'name' => 'Land Acquisition'],
                    ['code' => 'A.10', 'name' => 'Forest Clearance'],
                    ['code' => 'A.11', 'name' => 'Bidding Document Approval'],
                    ['code' => 'A.12', 'name' => 'Tendering & Contractor Selection'],
                ],
            ],

            // Phase B - Design Phase
            [
                'code' => 'B',
                'name' => 'Detailed Design & Engineering Phase',
                'weightage' => 15.00,
                'level' => 1,
                'sort_order' => 2,
                'children' => [
                    [
                        'code' => 'B.1',
                        'name' => 'Electrical Design',
                        'children' => [
                            ['code' => 'B.1.1', 'name' => 'Single Line Diagram (SLD)'],
                            ['code' => 'B.1.2', 'name' => 'Electrical Layout & Section Drawings'],
                            // Add more as needed...
                        ],
                    ],
                    [
                        'code' => 'B.2',
                        'name' => 'Civil & Structural Design',
                        'children' => [
                            ['code' => 'B.2.1', 'name' => 'Terace Formation Layout'],
                            ['code' => 'B.2.2', 'name' => 'Electrical Resistivity Test/Soil test'],
                            // Add more as needed...
                        ],
                    ],
                ],
            ],

            // Phase C - Construction
            [
                'code' => 'C',
                'name' => 'Construction & Installation Phase',
                'weightage' => 65.00,
                'level' => 1,
                'sort_order' => 3,
                'children' => [
                    [
                        'code' => 'C.1',
                        'name' => 'Civil Construction',
                        'children' => [
                            ['code' => 'C.1.1', 'name' => 'Site Preparation & Leveling'],
                            ['code' => 'C.1.2', 'name' => 'Foundation Work (total Concrete cu.m)'],
                            // Add more...
                        ],
                    ],
                    [
                        'code' => 'C.2',
                        'name' => 'Factory Testing & Dispatch Phase',
                        'children' => [
                            ['code' => 'C.2.2', 'name' => 'Gas Insulated Switchgears'],
                            ['code' => 'C.2.3', 'name' => 'Power Transformers'],
                            // Add more...
                        ],
                    ],
                    [
                        'code' => 'C.3',
                        'name' => 'Installation Phase',
                        'children' => [
                            ['code' => 'C.3.1', 'name' => 'Gantry & Steel Structure Erection'],
                            ['code' => 'C.3.2', 'name' => 'PEB GIS Building Erection'],
                            // Add more...
                        ],
                    ],
                ],
            ],

            // Phase D
            [
                'code' => 'D',
                'name' => 'Testing, Commissioning & Handover',
                'weightage' => 10.00,
                'level' => 1,
                'sort_order' => 4,
                'children' => [
                    ['code' => 'D.1', 'name' => 'Pre-Commissioning Tests of all Equipments'],
                    ['code' => 'D.2', 'name' => 'Indoor Switchgears Testing'],
                    ['code' => 'D.3', 'name' => 'Relay & SCADA Testing'],
                    ['code' => 'D.4', 'name' => 'FOTE testing'],
                    ['code' => 'D.5', 'name' => 'Final commissioning & Charging'],
                    ['code' => 'D.6', 'name' => 'Project Completion Certificate/Handover to Grid'],
                ],
            ],
        ];

        $this->insertSchedules($schedules, 'substation');
    }

    /**
     * Recursive function to insert schedules with parent-child relationships
     */
    private function insertSchedules(array $schedules, string $projectType, ?int $parentId = null, int $baseLevel = 1): void
    {
        foreach ($schedules as $index => $scheduleData) {
            $children = $scheduleData['children'] ?? [];
            unset($scheduleData['children']);

            $level = $scheduleData['level'] ?? $baseLevel;

            $schedule = ProjectActivitySchedule::create([
                'code' => $scheduleData['code'],
                'name' => $scheduleData['name'],
                'weightage' => $scheduleData['weightage'] ?? null,
                'parent_id' => $parentId,
                'project_type' => $projectType,
                'level' => $level,
                'sort_order' => $scheduleData['sort_order'] ?? ($index + 1),
            ]);

            // Recursively insert children
            if (!empty($children)) {
                $this->insertSchedules($children, $projectType, $schedule->id, $level + 1);
            }
        }
    }
}
