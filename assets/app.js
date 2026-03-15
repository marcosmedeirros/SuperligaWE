document.addEventListener('DOMContentLoaded', () => {
    if (typeof appState === 'undefined' || !appState || !appState.sprint) {
        return;
    }
    const params = new URLSearchParams(window.location.search);
    const tab = params.get('tab');
    if (tab) {
        switchTab(tab);
    }
    if (tab === 'team' && params.get('manage') === '1') {
        toggleManagePlayers();
    }
    document.getElementById('sprint-display').innerText = appState.sprint;
    const sel = document.getElementById('formation-select');
    if (sel) {
        sel.innerHTML = appState.formations.map(f => `<option class="bg-slate-900" value="${f}">${f}</option>`).join('');
    }

    const gm = appState.gm_logado;
    document.getElementById('dash-rank').innerText = `${gm.posicao_ranking}º`;
    document.getElementById('dash-pts').innerText = `${gm.pontos} pts`;
    document.getElementById('dash-cap-val').innerText = gm.cap_sum;
    document.getElementById('dash-cap-limit').innerText = gm.cap_limit;
    if (!gm.cap_ok) {
        document.getElementById('dash-cap-card').classList.add('border-red-500');
        document.getElementById('dash-cap-val').classList.add('text-red-400', 'animate-pulse');
    }
    document.getElementById('dash-athletes').innerText = gm.total_atletas;
    document.getElementById('dash-trades').innerText = gm.trade_count;

    const top5 = [...appState.gms].sort((a, b) => b.points - a.points).slice(0, 5);
    document.getElementById('dash-top5').innerHTML = top5.map((g, i) => `
        <div class="flex justify-between items-center p-2 rounded bg-slate-900/50 border border-slate-700/50">
            <div><span class="font-black text-slate-500 w-6 inline-block">${i + 1}º</span> <span class="font-bold text-white text-sm">${g.teamName}</span></div>
            <span class="text-emerald-400 font-mono text-sm">${g.points} pt</span>
        </div>`).join('');

    renderPitch();
    renderBench();
    renderRanking();
    renderCrudList();
    renderAllPlayersDatabase();
});

function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.getElementById(`tab-${tabId}`).classList.add('active');

    document.querySelectorAll('.nav-btn').forEach(btn => btn.className = 'nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-slate-400 hover:bg-slate-800 transition');
    const target = document.querySelector(`.nav-btn[data-target="${tabId}"]`);
    if (target) {
        if (tabId === 'admin') {
            target.className = 'nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-red-400 bg-red-900/20 mt-4 border border-transparent';
        } else {
            target.className = 'nav-btn w-full flex items-center px-4 py-3 text-sm rounded-xl text-emerald-400 bg-emerald-900/20 font-bold border border-emerald-500/50';
        }
    }
    if (window.innerWidth < 768) {
        document.getElementById('sidebar').classList.add('-translate-x-full');
    }
}

function renderAllPlayersDatabase() {
    const tbody = document.getElementById('all-players-body');
    if (!tbody) {
        return;
    }

    let html = '';
    appState.all_players.forEach(p => {
        let color = p.ovr >= 90 ? 'text-yellow-400' : (p.ovr >= 85 ? 'text-emerald-400' : 'text-slate-300');
        const isOwner = p.gm_dono === appState.gm_logado.nome_time;
        html += `
        <tr class="border-b border-slate-700/50 hover:bg-slate-800 transition">
            <td class="p-4 text-center font-black ${color} text-lg">${p.ovr}</td>
            <td class="p-4 font-bold text-white">${p.name}</td>
            <td class="p-4"><span class="bg-slate-800 px-2 py-1 rounded text-[10px] font-bold text-slate-400">${p.pos}</span></td>
            <td class="p-4 text-slate-300">${p.idade} anos</td>
            <td class="p-4 text-right">
                <div class="flex items-center justify-end gap-2">
                    <span class="inline-block px-3 py-1 rounded-full text-xs font-bold ${p.gm_dono === 'Livre' ? 'bg-slate-700 text-slate-300' : 'bg-blue-900/30 text-blue-400 border border-blue-500/30'}">${p.gm_dono}</span>
                    ${isOwner ? `<button onclick='editPlayer(${JSON.stringify(p)})' class="text-blue-400 hover:text-blue-300" title="Editar"><i class="fa-solid fa-pen"></i></button>` : ''}
                </div>
            </td>
        </tr>`;
    });
    tbody.innerHTML = html;
}

let manageMode = false;
function toggleManagePlayers() {
    manageMode = !manageMode;
    if (manageMode) {
        document.getElementById('view-pitch').classList.add('hidden');
        document.getElementById('view-manage').classList.remove('hidden');
    } else {
        document.getElementById('view-manage').classList.add('hidden');
        document.getElementById('view-pitch').classList.remove('hidden');
    }
}

function renderCrudList() {
    const tbody = document.getElementById('crud-players-list');
    if (!tbody) {
        return;
    }
    tbody.innerHTML = appState.players.map(p => `
        <tr class="border-b border-slate-700 hover:bg-slate-700/20">
            <td class="p-3 font-bold text-emerald-400">${p.ovr}</td>
            <td class="p-3 text-white font-medium">${p.name}</td>
            <td class="p-3 text-xs">${p.pos}</td>
            <td class="p-3 text-xs text-slate-400">${p.idade || 25}</td>
            <td class="p-3 text-right space-x-2">
                <button onclick='editPlayer(${JSON.stringify(p)})' class="text-blue-400 hover:text-blue-300"><i class="fa-solid fa-pen"></i></button>
                <button onclick='deletePlayer(${p.id})' class="text-red-400 hover:text-red-300"><i class="fa-solid fa-trash"></i></button>
            </td>
        </tr>
    `).join('');
}

function openPlayerModal() {
    document.getElementById('modal-title').innerText = "Novo Jogador";
    document.getElementById('modal_id').value = "";
    document.getElementById('modal_nome').value = "";
    document.getElementById('modal_ovr').value = "80";
    document.getElementById('modal_idade').value = "25";
    document.getElementById('player-modal').classList.remove('hidden');
}

function editPlayer(p) {
    document.getElementById('modal-title').innerText = "Editar " + p.name;
    document.getElementById('modal_id').value = p.id;
    document.getElementById('modal_nome').value = p.name;
    document.getElementById('modal_pos').value = p.pos;
    document.getElementById('modal_ovr').value = p.ovr;
    document.getElementById('modal_idade').value = p.idade || 25;
    document.getElementById('player-modal').classList.remove('hidden');
}

function closePlayerModal() {
    document.getElementById('player-modal').classList.add('hidden');
}

function deletePlayer(id) {
    if (confirm("Deseja realmente dispensar este jogador do seu elenco?")) {
        document.getElementById('delete_jogador_id').value = id;
        document.getElementById('delete-form').submit();
    }
}

const formationMap = {
    '4-3-3': ['GOL', 'LD', 'ZAG', 'ZAG', 'LE', 'MC', 'MC', 'MEI', 'PD', 'ATA', 'PE'],
    '4-4-2': ['GOL', 'LD', 'ZAG', 'ZAG', 'LE', 'MC', 'MC', 'MEI', 'MEI', 'ATA', 'ATA'],
    '4-2-3-1': ['GOL', 'LD', 'ZAG', 'ZAG', 'LE', 'VOL', 'VOL', 'MEI', 'MEI', 'MEI', 'ATA'],
    '3-5-2': ['GOL', 'ZAG', 'ZAG', 'ZAG', 'MC', 'MC', 'MEI', 'MEI', 'LD', 'LE', 'ATA'],
    '5-3-2': ['GOL', 'LD', 'ZAG', 'ZAG', 'ZAG', 'LE', 'MC', 'MC', 'MEI', 'ATA', 'ATA']
};

function getSelectedIds() {
    const selects = Array.from(document.querySelectorAll('.pitch-select'));
    return selects.map(s => parseInt(s.value, 10)).filter(v => !Number.isNaN(v));
}

function renderPitch() {
    const formation = document.getElementById('formation-select')?.value || '4-3-3';
    const positions = formationMap[formation] || formationMap['4-3-3'];
    const pitch = document.getElementById('pitch-container');
    if (!pitch) {
        return;
    }

    const titulares = appState.players.filter(p => p.is_titular);
    const reservas = appState.players.filter(p => !p.is_titular);
    const lineup = [...titulares, ...reservas].slice(0, 11);

    const lineBuckets = { GOL: [], DEF: [], MID: [], ATA: [] };
    positions.forEach((pos, idx) => {
        const current = lineup[idx];
        if (pos === 'GOL') {
            lineBuckets.GOL.push({ pos, current });
        } else if (['ZAG', 'LD', 'LE'].includes(pos)) {
            lineBuckets.DEF.push({ pos, current });
        } else if (['VOL', 'MC', 'MEI'].includes(pos)) {
            lineBuckets.MID.push({ pos, current });
        } else {
            lineBuckets.ATA.push({ pos, current });
        }
    });

    const buildSlot = ({ pos, current }) => {
        const options = appState.players.map(p => {
            const selected = current && p.id === current.id ? 'selected' : '';
            return `<option value="${p.id}" ${selected}>${p.name} (${p.pos}) OVR ${p.ovr}</option>`;
        }).join('');
        return `
        <div class="flex flex-col items-center gap-2">
            <div class="text-[10px] font-bold text-slate-200 bg-slate-900/60 px-2 py-1 rounded">${pos}</div>
            <select class="pitch-select bg-slate-900 border border-slate-700 text-xs text-white rounded-lg px-2 py-1" data-prev="${current ? current.id : ''}">
                ${options}
            </select>
        </div>`;
    };

    const buildLine = (label, items) => {
        if (items.length === 0) {
            return '';
        }
        return `
        <div class="flex flex-col items-center gap-2">
            <div class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">${label}</div>
            <div class="flex items-center justify-center gap-3 flex-wrap">
                ${items.map(buildSlot).join('')}
            </div>
        </div>`;
    };

    pitch.innerHTML = `
        <div class="absolute inset-0 p-4 flex flex-col justify-between">
            ${buildLine('Goleiro', lineBuckets.GOL)}
            ${buildLine('Defensores', lineBuckets.DEF)}
            ${buildLine('Meio', lineBuckets.MID)}
            ${buildLine('Ataque', lineBuckets.ATA)}
        </div>
    `;

    document.querySelectorAll('.pitch-select').forEach(sel => {
        sel.addEventListener('change', (e) => {
            const val = parseInt(e.target.value, 10);
            const selected = getSelectedIds();
            const duplicates = selected.filter((v, i) => selected.indexOf(v) !== i);
            if (duplicates.length > 0) {
                const prev = e.target.getAttribute('data-prev');
                if (prev) {
                    e.target.value = prev;
                }
                alert('Este jogador ja esta escalado.');
                return;
            }
            e.target.setAttribute('data-prev', val);
            renderBench();
        });
    });

    renderBench();
}

function renderBench() {
    const bench = document.getElementById('bench-list');
    if (!bench) {
        return;
    }
    const formation = document.getElementById('formation-select')?.value || '4-3-3';
    const positions = formationMap[formation] || formationMap['4-3-3'];
    const posOrder = new Map();
    positions.forEach((pos, idx) => {
        if (!posOrder.has(pos)) {
            posOrder.set(pos, idx);
        }
    });
    const fallbackOrder = ['GOL', 'ZAG', 'LD', 'LE', 'VOL', 'MC', 'MEI', 'PD', 'PE', 'ATA'];
    fallbackOrder.forEach((pos, idx) => {
        if (!posOrder.has(pos)) {
            posOrder.set(pos, positions.length + idx);
        }
    });
    const selected = new Set(getSelectedIds());
    const reservas = appState.players
        .filter(p => !selected.has(p.id))
        .sort((a, b) => {
            const aPos = posOrder.get(a.pos) ?? 999;
            const bPos = posOrder.get(b.pos) ?? 999;
            if (aPos !== bPos) {
                return aPos - bPos;
            }
            return b.ovr - a.ovr;
        });
    if (reservas.length === 0) {
        bench.innerHTML = `<div class="text-xs text-slate-500 p-2 text-center">Sem reservas.</div>`;
        return;
    }
    bench.innerHTML = reservas.map(p => `
        <div class="flex items-center justify-between bg-slate-800/50 border border-slate-700 rounded-lg px-3 py-2">
            <div>
                <div class="text-xs font-bold text-white">${p.name}</div>
                <div class="text-[10px] text-slate-400">${p.pos} • OVR ${p.ovr}</div>
            </div>
            <button type="button" class="text-emerald-400 text-xs font-bold" onclick="swapIntoLineup(${p.id})">Escalar</button>
        </div>
    `).join('');
}

function swapIntoLineup(playerId) {
    const selects = Array.from(document.querySelectorAll('.pitch-select'));
    const selected = new Set(getSelectedIds());
    if (selected.has(playerId)) {
        return;
    }
    const target = selects.find(s => s && s.value);
    if (!target) {
        return;
    }
    target.value = playerId;
    target.setAttribute('data-prev', playerId);
    renderBench();
}

document.getElementById('lineup-form')?.addEventListener('submit', () => {
    const ids = getSelectedIds();
    document.getElementById('titulares-input').value = ids.join(',');
});

function renderRanking() {
}
