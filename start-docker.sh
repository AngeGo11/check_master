#!/bin/bash

echo "🚀 Démarrage de GSCV+ avec Docker..."

# Arrêter et supprimer les conteneurs existants
echo "🛑 Arrêt des conteneurs existants..."
docker-compose down

# Supprimer les volumes si nécessaire (optionnel)
if [ "$1" = "--clean" ]; then
    echo "🧹 Nettoyage des volumes..."
    docker-compose down -v
fi

# Construire et démarrer les conteneurs
echo "🔨 Construction des images..."
docker-compose build

echo "▶️  Démarrage des services..."
docker-compose up -d

# Attendre que MySQL soit prêt
echo "⏳ Attente du démarrage de MySQL..."
sleep 30

# Vérifier le statut des conteneurs
echo "📊 Statut des conteneurs:"
docker-compose ps

echo ""
echo "✅ GSCV+ est maintenant accessible:"
echo "   🌐 Application: http://localhost:8000"
echo "   🗄️  phpMyAdmin: http://localhost:8080"
echo "   📊 MySQL: localhost:3306"
echo ""
echo "🔑 Connexion phpMyAdmin:"
echo "   Utilisateur: root"
echo "   Mot de passe: root"
echo ""
echo "📝 Commandes utiles:"
echo "   docker-compose logs -f    # Voir les logs"
echo "   docker-compose down       # Arrêter les services"
echo "   docker-compose restart    # Redémarrer les services" 