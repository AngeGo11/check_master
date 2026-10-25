import { Head, Link, usePage } from '@inertiajs/react';
import Icon from '../../components/icon';
import AppLayout from '../../layouts/app-layout';
import type { SharedData } from '../../types';

export default function Settings() {
  const { auth } = usePage<SharedData>().props;
  return <AppLayout title="Paramètres généraux" subtitle="Configuration de la plateforme">
    <Head title="Paramètres généraux" />
    <p className="breadcrumb"><Link href="/dashboard">Accueil</Link><span>/</span><strong>Paramètres généraux</strong></p>
    <div className="page-intro"><div><h1>Configuration du système</h1><p>Gérez les comptes, leurs accès et les paramètres pédagogiques.</p></div></div>
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
      {auth.permissions.includes('academic.view') && <section className="panel settings-card">
        <span className="panel-icon"><Icon name="graduate" /></span><h2>Promotions et niveaux</h2>
        <p>Consultez les niveaux et statuts étudiants, et gérez les promotions.</p>
        <Link href="/settings/academic" className="button-primary">Accéder aux paramètres</Link>
      </section>}
      {auth.permissions.includes('schooling.view') && <section className="panel settings-card"><span className="panel-icon"><Icon name="graduate" /></span><h2>Années et tarifs</h2><p>Définissez l’année en cours et les frais de scolarité par niveau.</p><Link href="/settings/schooling" className="button-primary">Accéder aux paramètres</Link></section>}
      {auth.permissions.includes('audit.view') && <section className="panel settings-card"><span className="panel-icon"><Icon name="clock" /></span><h2>Piste d’audit</h2><p>Retrouvez les actions enregistrées, leurs auteurs et les dossiers concernés.</p><Link href="/audit" className="button-primary">Consulter le journal</Link></section>}
    </div>
  </AppLayout>;
}
