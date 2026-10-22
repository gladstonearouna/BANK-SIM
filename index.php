
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Accueil - BANK-SIM</title>
<link rel="stylesheet" href="./accueil.css?v=2">
</head>

<body>

    <!-- Navigation principale -->
    <header class="navbar">

        <a href="index.php" class="logo">
            <span class="logo-icon">B</span>
            <span>BANK-SIM</span>
        </a>

        <nav class="menu">
            <a href="index.php">Accueil</a>
            <a href="#services">Nos services</a>
            <a href="#apropos">À propos</a>
            <a href="#contact">Contact</a>
        </nav>

        <div class="actions">
            <a href="login.php" class="btn btn-outline">Connexion</a>

            <a href="demande_ouverture.php" class="btn btn-primary">
                Créer un compte
            </a>
        </div>

    </header>

    <!-- Présentation de BANK-SIM -->
    <main>

        <section class="hero">

            <div class="hero-content">

                <span class="eyebrow">VOTRE BANQUE, VOTRE AVENIR</span>

                <h1>
                    Bienvenue chez
                    <span>BANK-SIM</span>
                </h1>

                <p class="hero-text">
                    Une banque qui vous accompagne au quotidien.
                    Découvrez une expérience bancaire simple,
                    moderne et pensée pour votre tranquillité.
                </p>

                <div class="hero-buttons">

                    <a href="demande_ouverture.php" class="btn btn-primary">
                        Ouvrir un compte
                    </a>

                    <a href="#services" class="btn btn-light">
                        Découvrir nos services
                    </a>

                </div>

                <p class="security-note">
                    Votre confiance est au cœur de nos priorités.
                </p>

            </div>

            <div class="hero-image">

                <img
                    src="https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=1200&q=85"
                    alt="Bâtiment moderne à l'architecture vitrée">

            </div>

        </section>

        <!-- Services bancaires -->
        <section class="services" id="services">

            <div class="section-heading">
                <span class="eyebrow">NOS SERVICES</span>

                <h2>La banque au service de vos projets</h2>

                <p>
                    Retrouvez les principaux services de BANK-SIM.
                </p>
            </div>

            <div class="service-grid">

                <article class="service-card">
                    <div class="service-icon">🔐</div>

                    <h3>Sécurité</h3>

                    <p>
                        Un accès protégé à votre espace bancaire.
                    </p>
                </article>

                <article class="service-card">
                    <div class="service-icon">💳</div>

                    <h3>Gestion des comptes</h3>

                    <p>
                        Consultez vos comptes et suivez vos opérations.
                    </p>
                </article>

                <article class="service-card">
                    <div class="service-icon">↗</div>

                    <h3>Virements</h3>

                    <p>
                        Gérez vos bénéficiaires et vos virements
                        depuis votre espace client.
                    </p>
                </article>

            </div>

        </section>

        <!-- À propos -->
        <section class="about" id="apropos">

            <div>
                <span class="eyebrow">À PROPOS</span>

                <h2>Une expérience bancaire plus simple</h2>
            </div>

            <p>
                BANK-SIM est une plateforme bancaire de démonstration
                conçue pour faciliter la gestion des comptes,
                des bénéficiaires et des transactions.
            </p>

        </section>

    </main>

    <!-- Pied de page -->
    <footer class="footer" id="contact">

        <a href="index.php" class="logo footer-logo">
            <span class="logo-icon">B</span>
            <span>BANK-SIM</span>
        </a>

        <p>Une expérience bancaire moderne et accessible.</p>

        <p class="copyright">
            © 2026 BANK-SIM — Projet de démonstration.
        </p>

    </footer>

</body>

</html>
