<?php

namespace Database\Seeders;

use App\Models\Classe;
use App\Models\Module;
use App\Models\TeacherAssignment;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $classes = collect([
            ['code' => 'DEV-101', 'annee' => '1'],
            ['code' => 'DEV-102', 'annee' => '1'],
            ['code' => 'DEV-103', 'annee' => '1'],
            ['code' => 'DEV-201', 'annee' => '2'],
            ['code' => 'DEV-202', 'annee' => '2'],
            ['code' => 'DEV-203', 'annee' => '2'],
            ['code' => 'DEV-204', 'annee' => '2'],
            ['code' => 'DEV-205', 'annee' => '2'],
            ['code' => 'DEV-206', 'annee' => '2'],
        ])->mapWithKeys(fn ($classe) => [$classe['code'] => Classe::updateOrCreate(['code' => $classe['code']], $classe)]);

        $modules = collect([
            ['code' => 'M102', 'intitule' => 'Metier et formation', 'annee' => '1'],
            ['code' => 'M103', 'intitule' => 'Algorithmique', 'annee' => '1'],
            ['code' => 'M104', 'intitule' => 'Programmation orientee objet', 'annee' => '1'],
            ['code' => 'M105', 'intitule' => 'Bases de donnees', 'annee' => '1'],
            ['code' => 'M106', 'intitule' => 'Developpement web cote client', 'annee' => '1'],
            ['code' => 'M107', 'intitule' => 'Developpement web cote serveur', 'annee' => '1'],
            ['code' => 'M108', 'intitule' => 'Projet de synthese 1', 'annee' => '1'],
            ['code' => 'EGTS202', 'intitule' => 'Francais applique', 'annee' => '2'],
            ['code' => 'EGTS203', 'intitule' => 'Anglais technique', 'annee' => '2'],
            ['code' => 'EGTS204', 'intitule' => 'Culture entrepreneuriale', 'annee' => '2'],
            ['code' => 'EGTS205', 'intitule' => 'Competences comportementales', 'annee' => '2'],
            ['code' => 'EGTS208', 'intitule' => 'Droit et legislation du travail', 'annee' => '2'],
            ['code' => 'EGTSA206', 'intitule' => 'Arabe professionnel', 'annee' => '2'],
            ['code' => 'M201', 'intitule' => 'Developpement front-end', 'annee' => '2'],
            ['code' => 'M202', 'intitule' => 'Developpement back-end', 'annee' => '2'],
            ['code' => 'M203', 'intitule' => 'Developpement mobile', 'annee' => '2'],
            ['code' => 'M204', 'intitule' => 'Frameworks web', 'annee' => '2'],
            ['code' => 'M205', 'intitule' => 'Administration de bases de donnees', 'annee' => '2'],
            ['code' => 'M206', 'intitule' => 'Tests et qualite logicielle', 'annee' => '2'],
            ['code' => 'M207', 'intitule' => 'DevOps et deploiement', 'annee' => '2'],
            ['code' => 'M208', 'intitule' => 'Projet de synthese 2', 'annee' => '2'],
        ])->mapWithKeys(fn ($module) => [$module['code'] => Module::updateOrCreate(['code' => $module['code']], $module)]);

        User::updateOrCreate(['email' => 'younes_sedki@hotmail.fr'], [
            'nom' => 'Sedki',
            'prenom' => 'Younes',
            'password' => Hash::make('etudiant123'),
            'role' => 'etudiant',
            'telephone' => '+212600000001',
            'etablissement' => 'ISTA',
            'cne' => 'CNE001',
            'classe_id' => $classes['DEV-101']->id,
            'filiere' => 'Developpement Digital',
        ]);

        $teacher = User::updateOrCreate(['email' => 'rachid.benali@ista.ma'], [
            'nom' => 'Benali',
            'prenom' => 'Rachid',
            'password' => Hash::make('enseignant123'),
            'role' => 'enseignant',
            'telephone' => '+212600000002',
            'etablissement' => 'ISTA',
        ]);

        User::updateOrCreate(['email' => 'admin@ista.ma'], [
            'nom' => 'Admin',
            'prenom' => 'ISTA',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
            'telephone' => '+212600000003',
            'etablissement' => 'ISTA',
        ]);

        foreach (['DEV-101', 'DEV-102'] as $code) {
            TeacherAssignment::updateOrCreate([
                'user_id' => $teacher->id,
                'classe_id' => $classes[$code]->id,
                'module_id' => $modules['M102']->id,
            ]);
        }
    }
}
