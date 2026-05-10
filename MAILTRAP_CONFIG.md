# Configuration Mailtrap pour TaskFlow

## Installation et Configuration

### 1. Créer un compte Mailtrap
1. Allez sur [https://mailtrap.io](https://mailtrap.io)
2. Créez un compte gratuit
3. Accédez à "Integrations" → "Symfony"

### 2. Configuration dans `.env.local`

Ajoutez ces lignes (remplacez par vos identifiants Mailtrap) :

```env
MAILER_DSN=smtp://username:password@smtp.mailtrap.io:2525?encryption=tls
```

Vous trouverez les identifiants dans votre tableau de bord Mailtrap :
- **SMTP Host** : `smtp.mailtrap.io`
- **SMTP Port** : `2525`
- **Username** et **Password** : dans votre inbox settings

### 3. Vérifier la configuration

```bash
# Tester l'envoi d'un email
php bin/console mailer:test admin@taskflow.com
```

### 4. Accéder aux emails envoyés

Tous les emails envoyés en development vont à votre inbox Mailtrap. Accédez à [https://mailtrap.io/inboxes](https://mailtrap.io/inboxes) pour les voir.

---

## Utilisateurs de test

| Email | Mot de passe | Rôle |
|-------|-------------|------|
| `admin@taskflow.com` | `admin123` | Admin |
| `chef@taskflow.com` | `chef123` | Chef de Projet |
| `user1@taskflow.com` | `user123` | Utilisateur |
| `user2@taskflow.com` | `user123` | Utilisateur |
| `user3@taskflow.com` | `user123` | Utilisateur |
| `user4@taskflow.com` | `user123` | Utilisateur |
| `user5@taskflow.com` | `user123` | Utilisateur |

---

## Charger les fixtures

```bash
php bin/console doctrine:fixtures:load -n
```

Cela charge :
- 6 étiquettes prédéfinies
- 7 utilisateurs (1 admin + 1 chef + 5 utilisateurs)
- 8 projets
- 40 tâches réparties entre les projets
