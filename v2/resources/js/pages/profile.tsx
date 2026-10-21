import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '../layouts/app-layout';
import type { SharedData } from '../types';

export default function Profile() {
  const { auth } = usePage<SharedData>().props;
  return <AppLayout title="Profils et informations" subtitle="Votre compte Check Master">
    <Head title="Mon profil" /><p className="breadcrumb"><Link href="/dashboard">Accueil</Link><span>/</span><strong>Mon profil</strong></p>
    <div className="page-intro"><div><h1>Mon profil</h1><p>Consultez vos informations et vos accès.</p></div></div>
    <section className="panel management-form" aria-label="Informations du compte"><dl className="profile-details">
      <div><dt>Nom complet</dt><dd>{auth.user?.name}</dd></div><div><dt>Adresse e-mail</dt><dd>{auth.user?.email}</dd></div>
      <div><dt>Types d’utilisateur</dt><dd>{auth.user?.types.map((type) => type.name).join(', ') || 'Non attribué'}</dd></div>
      <div><dt>Groupes utilisateurs</dt><dd>{auth.user?.groups.map((group) => group.name).join(', ') || 'Aucun groupe attribué'}</dd></div>
    </dl><p className="field-hint">Pour corriger vos informations ou vos accès, contactez l’administration.</p><Link href="/password/change" className="button-primary">Modifier mon mot de passe</Link></section>
  </AppLayout>;
}
