import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type PropsWithChildren } from 'react';
import Brand from '../components/brand';
import Icon from '../components/icon';
import type { SharedData } from '../types';

export default function AppLayout({ children }: PropsWithChildren) {
  const { auth } = usePage<SharedData>().props;
  const [menuOpen, setMenuOpen] = useState(false);
  const menuToggle = useRef<HTMLButtonElement>(null);
  const initials = auth.user?.name.trim().split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || 'CM';

  useEffect(() => {
    if (!menuOpen) return;
    function closeOnEscape(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        setMenuOpen(false);
        menuToggle.current?.focus();
      }
    }
    document.addEventListener('keydown', closeOnEscape);
    return () => document.removeEventListener('keydown', closeOnEscape);
  }, [menuOpen]);

  return (
    <div className="workspace">
      <a className="skip-link" href="#main">Aller au contenu</a>
      <header className="topbar">
        <div className="topbar-heading">
          <button ref={menuToggle} type="button" className="menu-toggle" aria-controls="primary-navigation"
            aria-expanded={menuOpen} aria-label={menuOpen ? 'Fermer le menu' : 'Ouvrir le menu'}
            onClick={() => setMenuOpen(!menuOpen)}>
            <Icon name={menuOpen ? 'close' : 'menu'} />
          </button>
          <span className="university-mark"><img src="/images/logo-miage.png" alt="MIAGE" width={44} height={44} /></span>
          <div><p className="workspace-title">Tableau de bord</p><p className="workspace-subtitle">Gestion de commission de validation</p></div>
        </div>
        <a href="#mon-profil" className="account" onClick={() => setMenuOpen(false)} aria-label="Consulter mon profil">
          <span className="account-name">{auth.user?.name}</span>
          <span className="avatar" aria-hidden="true">{initials}</span>
        </a>
      </header>
      <aside className={'sidebar' + (menuOpen ? ' is-open' : '')}>
        <div className="sidebar-brand">
          <Link href="/dashboard" className="brand-link" aria-label="Check Master — tableau de bord"><Brand stacked /></Link>
          <p>Commission de validation</p>
        </div>
        <nav id="primary-navigation" aria-label="Navigation principale">
          <p className="nav-label">MENU PRINCIPAL</p>
          <Link href="/dashboard" className="nav-item nav-active" aria-current="page" onClick={() => setMenuOpen(false)}>
            <Icon name="dashboard" /> Tableau de bord
          </Link>
          <a href="#mon-profil" className="nav-item" onClick={() => setMenuOpen(false)}><Icon name="user" /> Mon profil</a>
          <Link href="/logout" method="post" as="button" className="nav-item" onClick={() => setMenuOpen(false)}>
            <Icon name="logout" /> Déconnexion
          </Link>
        </nav>
        <div className="sidebar-footer">
          <div className="sidebar-user"><span className="avatar" aria-hidden="true">{initials}</span><span>{auth.user?.name}</span></div>
          <p>UFHB · MIAGE</p>
        </div>
      </aside>
      <main id="main" className="main-content" tabIndex={-1}>{children}</main>
      <footer className="page-footer">CHECK MASTER · Université Félix Houphouët-Boigny</footer>
    </div>
  );
}
