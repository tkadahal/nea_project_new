<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Contract;
use Illuminate\Database\Seeder;

class ContractsTableSeeder extends Seeder
{
    public function run(): void
    {
        $contracts = [

            [

                'directorate_id' => 1,
                'project_id' => 128,
                'status_id' => 2,
                'priority_id' => 3,
                'title' => 'Constrution of 11 kV LIne',
                'description' => 'Nawalpur',
                'contractor' => 'AB Nirman Sewa',
                'contract_amount' => '100000.00',
                'contract_variation_amount' => '0.00',
                'contract_agreement_date' => '2024-01-01 00:00:00',
                'agreement_effective_date' => '2024-01-15 00:00:00',
                'agreement_completion_date' => '2026-12-31 00:00:00',
                'initial_contract_period' => '500',
                'progress' => 40,
                'created_at' => '2025-09-03 09:29:22',
                'updated_at' => '2025-09-03 09:29:22',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'project_id' => 4,
                'status_id' => 2,
                'priority_id' => 2,
                'title' => 'CONTRACT : ICB-PMD-MKTLP-076/077-02',
                'description' => 'Design, Supply, Installation and Commissioning of 220kV Air Insulated Substation (AIS) in Matatirtha, Kathmandu and 220kV Gas Insulated Substation (GIS) in Markichowk, Marsyangdi Kathmandu and 220kV Gas Insulated Substation (GIS) in Markichowk, Marsyangdi',
                'contractor' => 'CMEC (China Machinery Engineering Company), CHINA',
                'contract_amount' => '1964426593.84',
                'contract_variation_amount' => '265648551.00',
                'contract_agreement_date' => '2020-12-31 00:00:00',
                'agreement_effective_date' => '2021-03-15 00:00:00',
                'agreement_completion_date' => '2022-09-06 00:00:00',
                'initial_contract_period' => '540',
                'progress' => 92,
                'created_at' => '2025-09-03 13:45:46',
                'updated_at' => '2025-09-03 13:45:46',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'project_id' => 37,
                'status_id' => 2,
                'priority_id' => 2,
                'title' => 'PMD/EGMPAF/CPCUGTLP-079/80-01',
                'description' => 'Design, Supply, Installation, Testing and Commissioning of New Patan 132/66/11 kV GIS Substation (Package A1.2)',
                'contractor' => 'TBEA Co., Ltd',
                'contract_amount' => '1760558232.83',
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2023-12-29 00:00:00',
                'agreement_effective_date' => '2024-03-22 00:00:00',
                'agreement_completion_date' => '2025-11-12 00:00:00',
                'initial_contract_period' => '600',
                'progress' => 15,
                'created_at' => '2025-09-05 07:54:34',
                'updated_at' => '2025-09-05 07:54:34',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'project_id' => 37,
                'status_id' => 2,
                'priority_id' => 1,
                'title' => 'PMD/EGMPAF/CPCUGTLP-079/80-02',
                'description' => 'Design, Supply, Installation and Commissioning of 132 kV Underground Transmission Line from Chobhar Substation to New Patan Substation',
                'contractor' => 'Ravin Infraproject Pvt Ltd',
                'contract_amount' => '1064629465.23',
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2023-12-21 00:00:00',
                'agreement_effective_date' => '2024-02-19 00:00:00',
                'agreement_completion_date' => '2025-05-14 00:00:00',
                'initial_contract_period' => '450',
                'progress' => 11,
                'created_at' => '2025-09-05 08:00:58',
                'updated_at' => '2025-09-05 08:00:58',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'project_id' => 37,
                'status_id' => 2,
                'priority_id' => 1,
                'title' => 'PMD/EGMPAF/CPCUGTLP-079/80-02',
                'description' => 'Design, Supply, Installation and Commissioning of 132 kV Underground Transmission Line from Chobhar Substation to New Patan Substation',
                'contractor' => 'Ravin Infraproject Pvt Ltd',
                'contract_amount' => '1064629465.23',
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2023-12-21 00:00:00',
                'agreement_effective_date' => '2024-02-19 00:00:00',
                'agreement_completion_date' => '2025-05-14 00:00:00',
                'initial_contract_period' => '450',
                'progress' => 11,
                'created_at' => '2025-09-05 08:01:18',
                'updated_at' => '2025-09-05 08:02:05',
                'deleted_at' => '2025-09-05 08:02:05',
            ],

            [

                'directorate_id' => 4,
                'project_id' => 37,
                'status_id' => 2,
                'priority_id' => 1,
                'title' => 'PMD/EGMPAF/CPCUGTLP-079/80-02',
                'description' => 'Design, Supply, Installation and Commissioning of 132 kV Underground Transmission Line from Chobhar Substation to New Patan Substation',
                'contractor' => 'Ravin Infraproject Pvt Ltd',
                'contract_amount' => '1064629465.23',
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2023-12-21 00:00:00',
                'agreement_effective_date' => '2024-02-19 00:00:00',
                'agreement_completion_date' => '2025-05-14 00:00:00',
                'initial_contract_period' => '450',
                'progress' => 11,
                'created_at' => '2025-09-05 08:01:15',
                'updated_at' => '2025-09-05 08:02:30',
                'deleted_at' => '2025-09-05 08:02:30',
            ],

            [

                'directorate_id' => 4,
                'project_id' => 37,
                'status_id' => 2,
                'priority_id' => 1,
                'title' => 'PMD/EGMPAF/CPCUGTLP-079/80-02',
                'description' => 'Design, Supply, Installation and Commissioning of 132 kV Underground Transmission Line from Chobhar Substation to New Patan Substation',
                'contractor' => 'Ravin Infraproject Pvt Ltd',
                'contract_amount' => '1064629465.23',
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2023-12-21 00:00:00',
                'agreement_effective_date' => '2024-02-19 00:00:00',
                'agreement_completion_date' => '2025-05-14 00:00:00',
                'initial_contract_period' => '450',
                'progress' => 11,
                'created_at' => '2025-09-05 08:01:03',
                'updated_at' => '2025-09-05 08:02:38',
                'deleted_at' => '2025-09-05 08:02:38',
            ],

            [

                'directorate_id' => 4,
                'project_id' => 37,
                'status_id' => 2,
                'priority_id' => 1,
                'title' => 'PMD/EGMPAF/CPCUGTLP-079/80-02',
                'description' => 'Design, Supply, Installation and Commissioning of 132 kV Underground Transmission Line from Chobhar Substation to New Patan Substation',
                'contractor' => 'Ravin Infraproject Pvt Ltd',
                'contract_amount' => '1064629465.23',
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2023-12-21 00:00:00',
                'agreement_effective_date' => '2024-02-19 00:00:00',
                'agreement_completion_date' => '2025-05-14 00:00:00',
                'initial_contract_period' => '450',
                'progress' => 11,
                'created_at' => '2025-09-05 08:01:01',
                'updated_at' => '2025-09-05 08:02:45',
                'deleted_at' => '2025-09-05 08:02:45',
            ],

            [

                'directorate_id' => 4,
                'project_id' => 37,
                'status_id' => 2,
                'priority_id' => 1,
                'title' => 'PMD/EGMPAF/CPCUGTLP-079/80-02',
                'description' => 'Design, Supply, Installation and Commissioning of 132 kV Underground Transmission Line from Chobhar Substation to New Patan Substation',
                'contractor' => 'Ravin Infraproject Pvt Ltd',
                'contract_amount' => '1064629465.23',
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2023-12-21 00:00:00',
                'agreement_effective_date' => '2024-02-19 00:00:00',
                'agreement_completion_date' => '2025-05-14 00:00:00',
                'initial_contract_period' => '450',
                'progress' => 11,
                'created_at' => '2025-09-05 08:00:46',
                'updated_at' => '2025-09-05 08:02:57',
                'deleted_at' => '2025-09-05 08:02:57',
            ],

            [

                'directorate_id' => 4,
                'project_id' => 19,
                'status_id' => 2,
                'priority_id' => 1,
                'title' => 'PMD/PTDSSP/KVTCRPII-078/79-01',
                'description' => 'Design, Supply, Installation and Commissioning of 132 kV Underground Transmission Line from Bhaktapur SS to Thimi SS and Line Bay Extension Works at Bhaktapur',
                'contractor' => 'KEC International Ltd',
                'contract_amount' => '875648959.31',
                'contract_variation_amount' => '24103561.95',
                'contract_agreement_date' => '2022-12-14 00:00:00',
                'agreement_effective_date' => '2023-01-27 00:00:00',
                'agreement_completion_date' => '2024-04-21 00:00:00',
                'initial_contract_period' => '450',
                'progress' => 80,
                'created_at' => '2025-09-05 08:18:04',
                'updated_at' => '2025-09-05 08:18:04',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'project_id' => 14,
                'status_id' => 2,
                'priority_id' => 2,
                'title' => 'Enhancement of Distribution Network in Central Region of Kathmandu Valley',
                'description' => 'Design, Supply, Installation and Commissioning of Underground Distribution Network under Ratnapark Distribution Center including Reinforcement and Automation',
                'contractor' => 'M/s KEI Industries Limited, India',
                'contract_amount' => NULL,
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2019-03-15 00:00:00',
                'agreement_effective_date' => '2019-04-23 00:00:00',
                'agreement_completion_date' => '2025-11-09 00:00:00',
                'initial_contract_period' => '2392',
                'progress' => 96,
                'created_at' => '2025-10-14 05:59:43',
                'updated_at' => '2025-10-14 05:59:43',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'project_id' => 18,
                'status_id' => 2,
                'priority_id' => 3,
                'title' => 'PMD/PTDSSP/KBL-75/76-01',
                'description' => 'Design, Supply, Installation and Commissioning of 400 kV Gas insulated Substations (GIS) at New Khimti, Barhabise and Lapsiphedi',
                'contractor' => 'Grid Solutions SAS, France',
                'contract_amount' => '0.00',
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2020-10-02 00:00:00',
                'agreement_effective_date' => '2020-12-11 00:00:00',
                'agreement_completion_date' => '2026-06-29 00:00:00',
                'initial_contract_period' => '900',
                'progress' => 78,
                'created_at' => '2025-11-06 15:10:34',
                'updated_at' => '2025-11-06 15:13:28',
                'deleted_at' => NULL,
            ],

            [

                'directorate_id' => 4,
                'project_id' => 187,
                'status_id' => 2,
                'priority_id' => 2,
                'title' => 'PMD/PTDEEP/KVCNDSEP-074/75-01 (Re)',
                'description' => 'Enhancement of Distribution Networks in Northern Region of Kathmandu Valley (Design, Supply, Installation and Commissioning of Underground Distribution Network under Maharajgunj Distribution Center including Reinforcement and Automation)',
                'contractor' => 'KEI Industries Limited, India',
                'contract_amount' => NULL,
                'contract_variation_amount' => NULL,
                'contract_agreement_date' => '2019-03-15 00:00:00',
                'agreement_effective_date' => '2019-04-22 00:00:00',
                'agreement_completion_date' => '2025-06-09 00:00:00',
                'initial_contract_period' => '2240',
                'progress' => 88,
                'created_at' => '2025-11-07 06:42:19',
                'updated_at' => '2025-11-07 06:42:19',
                'deleted_at' => NULL,
            ],
        ];

        Contract::insert($contracts);
    }
}
