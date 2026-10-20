import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Brand from '../components/brand';
import type { SharedData } from '../types';

export default function AppLayout({ children }: PropsWithChildren) {
  const { auth } = usePage<SharedData>().props;
  return (
    <div className="workspace">
      <a className="skip-link" href="#main">Aller au contenu</a>
      <aside className="sidebar">
        <Link href="/dashboard" className="brand-link" aria-label="Check Master — accueil"><Brand /></Link>
        <p className="nav-label">MON ESPACE</p>
        <nav aria-label="Navigation principale">
          <Link href="/dashboard" className="nav-active" aria-current="page">
            <span aria-hidden="true">◈</span> Tableau de bord
          </Link>
        </nav>
        <div className="sidebar-footer">
          <span className="small-label">UFHB · MIAGE</span>
          <p>Gestion des mémoires<br />et des commissions.</p>
        </div>
      </aside>
      <div className="workspace-body">
        <header className="topbar">
          <span className="workspace-title">Espace de travail</span>
          <div className="account">
            <span className="account-name">{auth.user?.name}</span>
            <Link href="/logout" method="post" as="button" className="button-secondary">Se déconnecter</Link>
          </div>
        </header>
        <main id="main" className="main-content">{children}</main>
        <footer className="page-footer">Check Master · Université Félix Houphouët-Boigny</footer>
      </div>
    </div>
  );
}
