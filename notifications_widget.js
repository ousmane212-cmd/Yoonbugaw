
(function () {
  'use strict';


  function injectUI() {
   
    const style = document.createElement('style');
    style.textContent = `
      /* Cloche existante rendue interactive */
      .notif-btn{position:relative;cursor:pointer;display:flex;align-items:center;justify-content:center}
      .notif-btn .notif-dot{display:none}
      .notif-badge{position:absolute;top:-5px;right:-5px;background:#ef4444;color:#fff;border-radius:50%;font-size:9px;font-weight:700;min-width:17px;height:17px;display:none;align-items:center;justify-content:center;padding:0 3px;border:2px solid #fff;pointer-events:none}

      /* Panneau notifications */
      .notif-panel{position:fixed;top:68px;right:16px;width:360px;max-height:500px;background:#fff;border-radius:16px;border:1px solid #e2e8f0;box-shadow:0 8px 32px rgba(0,0,0,.14);z-index:99999;display:none;flex-direction:column;overflow:hidden}
      .notif-panel.open{display:flex}
      .notif-panel-head{display:flex;align-items:center;justify-content:space-between;padding:14px 16px 12px;border-bottom:1px solid #f1f5f9;flex-shrink:0}
      .notif-panel-title{font-size:15px;font-weight:700;color:#0f172a}
      .notif-markall{font-size:12px;color:#3b82f6;background:none;border:none;cursor:pointer;padding:0;font-family:inherit}
      .notif-markall:hover{text-decoration:underline}
      .notif-list{flex:1;overflow-y:auto;max-height:420px}
      .notif-list::-webkit-scrollbar{width:4px}
      .notif-list::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px}

      /* Items */
      .notif-item{display:flex;gap:10px;padding:11px 14px;border-bottom:1px solid #f8fafc;cursor:pointer;transition:background .1s;align-items:flex-start}
      .notif-item:last-child{border-bottom:none}
      .notif-item:hover{background:#f8fafc}
      .notif-item.unread{background:#f0f9ff}
      .ni-icon{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0}
      .ni-icon.success{background:#dcfce7;color:#166534}
      .ni-icon.warning{background:#fef9c3;color:#854d0e}
      .ni-icon.danger{background:#fee2e2;color:#b91c1c}
      .ni-icon.info{background:#dbeafe;color:#1e40af}
      .ni-body{flex:1;min-width:0}
      .ni-title{font-size:13px;font-weight:600;color:#0f172a;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
      .ni-msg{font-size:12px;color:#64748b;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
      .ni-time{font-size:11px;color:#94a3b8;margin-top:3px}
      .ni-dot{width:7px;height:7px;border-radius:50%;background:#3b82f6;flex-shrink:0;margin-top:5px}
      .ni-track-btn{display:inline-block;font-size:11px;color:#1d4ed8;background:none;border:1px solid #bfdbfe;border-radius:6px;padding:2px 8px;cursor:pointer;margin-top:4px;font-family:inherit}
      .ni-track-btn:hover{background:#eff6ff}
      .notif-empty{padding:32px 16px;text-align:center;color:#94a3b8;font-size:13px}
      .notif-empty i{font-size:28px;display:block;margin-bottom:8px;opacity:.4}

      /* Lien Notifications dans la sidebar */
      .sidebar .nav-item[data-notif] .nav-badge{display:inline-flex;align-items:center;justify-content:center;background:#ef4444;color:#fff;border-radius:10px;font-size:10px;font-weight:700;min-width:18px;height:18px;padding:0 4px;margin-left:auto}
    `;
    document.head.appendChild(style);

    
    let bellBtn = document.querySelector('.notif-btn');

    if (!bellBtn) {
      // Fallback : créer un bouton dans .topbar-right
      const topbarRight = document.querySelector('.topbar-right');
      if (topbarRight) {
        bellBtn = document.createElement('div');
        bellBtn.className = 'notif-btn';
        bellBtn.innerHTML = '<i class="bi bi-bell-fill"></i>';
        topbarRight.insertBefore(bellBtn, topbarRight.querySelector('.avatar-sm'));
      }
    }

    if (bellBtn) {
     
      const badge = document.createElement('span');
      badge.id        = 'notif-count';
      badge.className = 'notif-badge';
      badge.textContent = '0';
      bellBtn.appendChild(badge);
      bellBtn.id      = 'notif-bell';
      bellBtn.onclick = togglePanel;

      
      const sidebarNotifLink = document.querySelector('.sidebar .nav-item[href="#"]');
      // On cherche par icône
      document.querySelectorAll('.sidebar .nav-item').forEach(a => {
        if (a.querySelector('.bi-bell-fill')) {
          a.setAttribute('data-notif', '1');
          a.href = '#';
          a.onclick = (e) => { e.preventDefault(); togglePanel(); };
          const nb = document.createElement('span');
          nb.className = 'nav-badge';
          nb.id        = 'sidebar-notif-badge';
          nb.style.display = 'none';
          a.appendChild(nb);
        }
      });
    }

    
    const panel = document.createElement('div');
    panel.id        = 'notif-panel';
    panel.className = 'notif-panel';
    panel.innerHTML = `
      <div class="notif-panel-head">
        <span class="notif-panel-title"><i class="bi bi-bell-fill" style="margin-right:6px;font-size:14px"></i>Notifications</span>
        <button class="notif-markall" onclick="window._YBG_notif.markAll()">Tout marquer lu</button>
      </div>
      <div id="notif-list" class="notif-list">
        <div class="notif-empty"><i class="bi bi-bell-slash"></i>Chargement…</div>
      </div>
    `;
    document.body.appendChild(panel);

    // Fermer en cliquant ailleurs
    document.addEventListener('click', e => {
      if (!e.target.closest('#notif-panel') && !e.target.closest('#notif-bell')) {
        document.getElementById('notif-panel')?.classList.remove('open');
      }
    });

   
    window.addEventListener('resize', positionPanel);
  }

  function positionPanel() {
    const bell  = document.getElementById('notif-bell');
    const panel = document.getElementById('notif-panel');
    if (!bell || !panel) return;
    const rect  = bell.getBoundingClientRect();
    const right = window.innerWidth - rect.right;
    panel.style.right = Math.max(8, right - 8) + 'px';
    panel.style.top   = (rect.bottom + 8) + 'px';
  }

  function togglePanel() {
    const p = document.getElementById('notif-panel');
    if (!p) return;
    positionPanel();
    p.classList.toggle('open');
    if (p.classList.contains('open')) loadNotifs();
  }

 
  const ICONS = {
    success: '<i class="bi bi-check-circle-fill"></i>',
    warning: '<i class="bi bi-exclamation-triangle-fill"></i>',
    danger:  '<i class="bi bi-x-circle-fill"></i>',
    info:    '<i class="bi bi-bell-fill"></i>',
  };

  function timeAgo(dateStr) {
    const diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
    if (diff < 60)    return 'À l\'instant';
    if (diff < 3600)  return Math.floor(diff / 60) + ' min';
    if (diff < 86400) return Math.floor(diff / 3600) + 'h';
    return Math.floor(diff / 86400) + 'j';
  }

  
  function loadNotifs() {
    fetch('notification_api.php?action=get&limit=30')
      .then(r => r.json())
      .then(data => {
        if (!data.success) return;
        updateBadges(data.unread || 0);

        const list = document.getElementById('notif-list');
        if (!list) return;

        if (!data.notifications || !data.notifications.length) {
          list.innerHTML = '<div class="notif-empty"><i class="bi bi-bell-slash"></i>Aucune notification</div>';
          return;
        }

        list.innerHTML = data.notifications.map(n => {
          const type    = n.type || 'info';
          const unread  = n.lu == 0 || n.lu === '0';
          const trackBtn = (n.reservation_id && (n.type === 'success' || n.type === 'info'))
            ? `<button class="ni-track-btn" onclick="event.stopPropagation();location.href='tracking.php?reservation_id=${n.reservation_id}'">
                <i class="bi bi-geo-alt-fill" style="font-size:10px"></i> Suivre le trajet
               </button>`
            : '';
          return `<div class="notif-item${unread ? ' unread' : ''}"
              onclick="window._YBG_notif.markRead(${n.id}, this)">
            <div class="ni-icon ${type}">${ICONS[type] || ICONS.info}</div>
            <div class="ni-body">
              <div class="ni-title">${escHtml(n.titre)}</div>
              <div class="ni-msg">${escHtml(n.message)}</div>
              ${trackBtn}
              <div class="ni-time"><i class="bi bi-clock" style="font-size:10px"></i> ${timeAgo(n.created_at)}</div>
            </div>
            ${unread ? '<div class="ni-dot"></div>' : ''}
          </div>`;
        }).join('');
      })
      .catch(() => {});
  }

  function updateBadges(count) {
    // Badge cloche header
    const badge = document.getElementById('notif-count');
    if (badge) {
      badge.textContent    = count > 9 ? '9+' : count;
      badge.style.display  = count > 0 ? 'flex' : 'none';
    }
    // Badge sidebar
    const sb = document.getElementById('sidebar-notif-badge');
    if (sb) {
      sb.textContent   = count > 9 ? '9+' : count;
      sb.style.display = count > 0 ? 'inline-flex' : 'none';
    }
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }


  function markRead(id, el) {
    el.classList.remove('unread');
    el.querySelector('.ni-dot')?.remove();
    fetch('notification_api.php', {
      method: 'POST',
      body: new URLSearchParams({ action: 'mark_read', notif_id: id }),
    }).then(() => loadNotifs()).catch(() => {});
  }

  
  function markAll() {
    fetch('notification_api.php', {
      method: 'POST',
      body: new URLSearchParams({ action: 'mark_read' }),
    }).then(() => loadNotifs()).catch(() => {});
  }

 
  function startPolling() {
    loadNotifs();
    setInterval(loadNotifs, 30000);
  }


  window._YBG_notif = { markRead, markAll, loadNotifs };

  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => { injectUI(); startPolling(); });
  } else {
    injectUI(); startPolling();
  }

})();