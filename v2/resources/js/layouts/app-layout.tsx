import { Link, usePage } from '@inertiajs/react';
import { useEffect, useRef, useState, type PropsWithChildren } from 'react';
import Brand from '../components/brand';
import Icon from '../components/icon';
import type { SharedData } from '../types';

export default function AppLayout({ children, title = 'Tableau de bord', subtitle = 'Gestion de commission de validation' }: PropsWithChildren<{ title?: string; subtitle?: string }>) {
  const { auth, flash, unreadMessageCount } = usePage<SharedData>().props;
  const path = usePage().url.split('?')[0];
  const settingsAllowed = auth.permissions.includes('users.view') || auth.permissions.includes('groups.view') || auth.permissions.includes('academic.view') || auth.permissions.includes('schooling.view');
  const ownStudentRecord = auth.permissions.includes('students.self') && auth.user?.types.some((type) => type.id === 1);
  const needsPassword = auth.user?.must_change_password;
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
          <div><p className="workspace-title">{title}</p><p className="workspace-subtitle">{subtitle}</p></div>
        </div>
        <Link href={needsPassword ? '/password/change' : '/profile'} className="account" onClick={() => setMenuOpen(false)} aria-label="Consulter mon profil">
          <span className="account-name">{auth.user?.name}</span>
          <span className="avatar" aria-hidden="true">{initials}</span>
        </Link>
      </header>
      <aside className={'sidebar' + (menuOpen ? ' is-open' : '')}>
        <div className="sidebar-brand">
          <Link href="/dashboard" className="brand-link" aria-label="Check Master — tableau de bord"><Brand stacked /></Link>
          <p>{auth.user?.groups.map((group) => group.name).join(' · ') || 'Espace personnel'}</p>
        </div>
        <nav id="primary-navigation" aria-label="Navigation principale">
          <p className="nav-label">MENU PRINCIPAL</p>
          {!needsPassword && <><Link href="/dashboard" className={'nav-item' + (path === '/dashboard' ? ' nav-active' : '')} aria-current={path === '/dashboard' ? 'page' : undefined} onClick={() => setMenuOpen(false)}>
            <Icon name="dashboard" /> Tableau de bord
          </Link>
          {auth.permissions.includes('hr.view') && <Link href="/staff" className={'nav-item' + (path.startsWith('/staff') ? ' nav-active' : '')} aria-current={path.startsWith('/staff') ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="users" /> Ressources humaines</Link>}
          {auth.permissions.includes('hr.self') && auth.user?.types.some((t) => [2, 3, 4].includes(t.id)) && <Link href="/my-staff-record" className={'nav-item' + (path === '/my-staff-record' ? ' nav-active' : '')} aria-current={path === '/my-staff-record' ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="user" /> Ma fiche professionnelle</Link>}
          {auth.permissions.includes('students.view') && <Link href="/students" className={'nav-item' + (path.startsWith('/students') ? ' nav-active' : '')} aria-current={path.startsWith('/students') ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="graduate" /> Étudiants</Link>}
          {ownStudentRecord && <Link href="/my-student-record" className={'nav-item' + (path === '/my-student-record' ? ' nav-active' : '')} aria-current={path === '/my-student-record' ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="graduate" /> Mon dossier étudiant</Link>}
          {auth.permissions.includes('enrollments.view') && <Link href="/enrollments" className={'nav-item' + (path.startsWith('/enrollments') ? ' nav-active' : '')} aria-current={path.startsWith('/enrollments') ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="graduate" /> Inscriptions et règlements</Link>}
          {ownStudentRecord && <Link href="/my-enrollments" className={'nav-item' + (path.startsWith('/my-enrollments') ? ' nav-active' : '')} aria-current={path.startsWith('/my-enrollments') ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="graduate" /> Mes inscriptions et stage</Link>}
          {(auth.permissions.includes('reports.view') || (auth.permissions.includes('reports.assigned') && auth.user?.groups.some((group) => group.id === 9))) && <Link href="/reports" className={'nav-item' + (path.startsWith('/reports') ? ' nav-active' : '')} aria-current={path.startsWith('/reports') ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="document" /> {auth.permissions.includes('reports.view') ? 'Rapports / Mémoires' : 'Rapports à examiner'}</Link>}
          {auth.permissions.includes('reports.self') && auth.user?.types.some((type) => type.id === 1) && <Link href="/my-reports" className={'nav-item' + (path === '/my-reports' ? ' nav-active' : '')} aria-current={path === '/my-reports' ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="document" /> Mon rapport / Mémoire</Link>}
          {auth.permissions.includes('meetings.view') && <Link href="/meetings" className={'nav-item' + (path.startsWith('/meetings') ? ' nav-active' : '')} aria-current={path.startsWith('/meetings') ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="calendar" /> Réunions</Link>}
          {auth.permissions.includes('messages.use') && <Link href="/messages" className={'nav-item' + (path.startsWith('/messages') ? ' nav-active' : '')} aria-current={path.startsWith('/messages') ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="mail" /> Messagerie {unreadMessageCount > 0 && <span className="nav-count" aria-label={unreadMessageCount + ' messages non lus'}>{unreadMessageCount > 99 ? '99+' : unreadMessageCount}</span>}</Link>}
          {settingsAllowed && <Link href="/settings" className={'nav-item' + (path.startsWith('/settings') ? ' nav-active' : '')} aria-current={path.startsWith('/settings') ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="settings" /> Paramètres généraux</Link>}
          <Link href="/profile" className={'nav-item' + (path === '/profile' ? ' nav-active' : '')} aria-current={path === '/profile' ? 'page' : undefined} onClick={() => setMenuOpen(false)}><Icon name="user" /> Mon profil</Link></>}
          <Link href="/logout" method="post" as="button" className="nav-item" onClick={() => setMenuOpen(false)}>
            <Icon name="logout" /> Déconnexion
          </Link>
        </nav>
        <div className="sidebar-footer">
          <div className="sidebar-user"><span className="avatar" aria-hidden="true">{initials}</span><span>{auth.user?.name}</span></div>
          <p>UFHB · MIAGE</p>
        </div>
      </aside>
      <main id="main" className="main-content" tabIndex={-1}>
        {flash.success && <p className="notice notice-success" role="status">{flash.success}</p>}
        {children}
      </main>
      <footer className="page-footer">CHECK MASTER · Université Félix Houphouët-Boigny</footer>
    </div>
  );
}
