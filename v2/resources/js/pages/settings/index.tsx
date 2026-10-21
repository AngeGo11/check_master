import { Head, Link, usePage } from '@inertiajs/react';
import Icon from '../../components/icon';
import AppLayout from '../../layouts/app-layout';
import type { SharedData } from '../../types';

export default function Settings() {
  const { auth } = usePage<SharedData>().props;
  return <AppLayout title="Paramètres généraux" subtitle="Configuration de la plateforme">
    <Head title="Paramètres généraux" />
    <p className="breadcrumb"><Link href="/dashboard">Accueil</Link><span>/</span><strong>Paramètres généraux</strong></p>
    <div className="page-intro"><div><h1>Configuration du système</h1><p>Gérez les utilisateurs et leurs droits d’accès.</p></div></div>
    <div className="settings-grid">
      {auth.permissions.includes('users.view') && <section className="panel settings-card">
        <span className="panel-icon"><Icon name="users" /></span><h2>Utilisateurs</h2>
        <p>Consultez les comptes, leurs types, leurs groupes et leur statut.</p>
        <Link href="/settings/users" className="button-primary">Accéder à la liste</Link>
      </section>}
      {auth.permissions.includes('groups.view') && <section className="panel settings-card">
        <span className="panel-icon"><Icon name="settings" /></span><h2>Groupes utilisateurs</h2>
        <p>Retrouvez les groupes de la plateforme et leurs permissions.</p>
        <Link href="/settings/groups" className="button-primary">Accéder à la liste</Link>
      </section>}
    </div>
  </AppLayout>;
}
