# Gestion des Absences — Backend

API REST Laravel pour la gestion des absences scolaires (PFE).

## Prérequis

- PHP 8.2+
- Composer
- MySQL ou SQLite

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configurer la base de données dans `.env`, puis :

```bash
php artisan migrate:fresh --seed
php artisan serve
```

L'API est disponible sur http://localhost:8000/api/v1

## Comptes de démonstration

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Étudiant | younes_sedki@hotmail.fr | etudiant123 |
| Enseignant | rachid.benali@ista.ma | enseignant123 |
| Admin | admin@ista.ma | admin123 |

## Endpoints principaux

- `POST /auth/login` — Connexion
- `GET /enseignant/assignments` — Affectations enseignant
- `POST /enseignant/sessions` — Créer une session d'appel
- `POST /enseignant/sessions/{id}/soumettre` — Soumettre l'appel
- `GET /etudiant/absences` — Absences de l'étudiant
- `POST /etudiant/absences/{id}/justifier` — Envoyer une justification
- `GET /admin/justifications` — Justifications en attente
- `POST /admin/justifications/{id}/accepter` — Accepter
- `POST /admin/justifications/{id}/rejeter` — Rejeter

## Parcours de démo

1. Enseignant soumet un appel avec des absences.
2. Étudiant dépose une justification.
3. Admin accepte ou rejette la justification.
