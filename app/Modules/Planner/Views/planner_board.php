<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<style>
:root { --nb: 51px; }

#pb-wrap {
    display: flex;
    flex-direction: column;
    height: calc(100vh - var(--nb));
    overflow: hidden;
    font-size: 13px;
}

/* ── Top bar ── */
#pb-topbar {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 14px;
    background: #2c3e50;
    flex-shrink: 0;
    border-bottom: 2px solid #1a252f;
}
#pb-topbar h4 { margin: 0; color: #ecf0f1; font-size: 14px; letter-spacing: 1px; }
#pb-topbar input[type=date] { width: 148px; }
#pb-topbar .status { color: #95a5a6; font-size: 11px; margin-left: 4px; }

/* ── Main area ── */
#pb-main {
    display: flex;
    flex: 1;
    overflow: hidden;
}

/* ── Sidebar ── */
#pb-sidebar {
    width: 240px;
    min-width: 240px;
    overflow-y: auto;
    background: #f4f6f7;
    border-right: 2px solid #d5d8dc;
    padding: 8px;
}

.pool-box {
    background: #fff;
    border: 1px solid #d5d8dc;
    border-radius: 4px;
    margin-bottom: 10px;
    overflow: hidden;
}
.pool-box-title {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 8px;
    font-size: 11px;
    font-weight: bold;
    letter-spacing: .5px;
    color: #fff;
    background: #2c3e50;
    cursor: pointer;
    user-select: none;
}
.pool-box-title:hover { opacity: .9; }
.pool-box-title.green  { background: #27ae60; }
.pool-box-title.red    { background: #c0392b; }
.pool-title-left { display: flex; align-items: center; gap: 6px; }
.pool-badge {
    background: rgba(255,255,255,.25);
    border-radius: 8px;
    padding: 0 6px;
    font-size: 10px;
    font-weight: normal;
}
.pool-chevron { font-size: 10px; opacity: .8; transition: transform .2s; }
.pool-chevron.open { transform: rotate(0deg); }
.pool-chevron { transform: rotate(-90deg); }

.pool-search-wrap { padding: 5px 5px 0; }
.pool-search {
    width: 100%;
    padding: 3px 7px;
    font-size: 11px;
    border: 1px solid #d5d8dc;
    border-radius: 3px;
    outline: none;
}
.pool-search:focus { border-color: #2980b9; }

.pool-list {
    min-height: 34px;
    padding: 5px;
}
.pool-empty {
    color: #aaa;
    font-size: 11px;
    padding: 4px 2px;
}

/* ── Worker chips (pool) ── */
.w-chip {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 7px;
    margin: 2px 0;
    background: #2980b9;
    color: #fff;
    border-radius: 4px;
    font-size: 12px;
    cursor: grab;
    user-select: none;
    transition: background .15s;
}
.w-chip:hover { background: #1f618d; }
.w-chip.sortable-ghost { opacity: .35; }
.w-chip.sortable-chosen { box-shadow: 0 2px 8px rgba(0,0,0,.3); }

/* ── Equipment chips (pool) ── */
.e-chip {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 4px 7px;
    margin: 2px 0;
    background: #229954;
    color: #fff;
    border-radius: 4px;
    font-size: 11px;
    cursor: grab;
    user-select: none;
    transition: background .15s;
}
.e-chip:hover { background: #1e8449; }
.e-chip.sortable-ghost { opacity: .35; }

/* ── Day-off entries ── */
.do-chip {
    padding: 4px 7px;
    margin: 2px 0;
    background: #e74c3c;
    color: #fff;
    border-radius: 4px;
    font-size: 11px;
}

/* ── Board (project columns) ── */
#pb-board {
    flex: 1;
    overflow-x: auto;
    overflow-y: auto;
    display: flex;
    gap: 10px;
    padding: 10px;
    align-items: flex-start;
    background: #eaecee;
}

#pb-no-data {
    color: #aaa;
    font-size: 14px;
    padding: 50px 30px;
}

/* ── Project column ── */
.proj-col {
    min-width: 290px;
    max-width: 330px;
    flex-shrink: 0;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #ced4da;
    box-shadow: 0 1px 3px rgba(0,0,0,.08);
}
.proj-header {
    padding: 8px 10px;
    background: #2c3e50;
    color: #fff;
    border-radius: 6px 6px 0 0;
}
.proj-header .proj-name { font-weight: bold; font-size: 12px; }
.proj-header .proj-obs  { font-size: 10px; color: #aab7b8; margin-top: 2px; word-break: break-word; }
.obs-edit-area {
    width: 100%;
    background: rgba(255,255,255,.1);
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 3px;
    color: #ecf0f1;
    font-size: 10px;
    padding: 3px 5px;
    resize: none;
    outline: none;
    margin-top: 3px;
    min-height: 28px;
    box-sizing: border-box;
}
.obs-edit-area:focus { border-color: rgba(255,255,255,.6); background: rgba(255,255,255,.15); }
.obs-edit-area::placeholder { color: rgba(255,255,255,.3); }
.obs-saved { display:none; font-size:10px; color:#2ecc71; }

.w-drop-zone {
    min-height: 50px;
    padding: 6px;
}
.w-drop-zone.over { background: rgba(52,152,219,.08); }
.w-drop-hint {
    border: 2px dashed #ced4da;
    border-radius: 4px;
    text-align: center;
    color: #aaa;
    font-size: 11px;
    padding: 10px;
    margin: 2px;
    pointer-events: none;
}

/* ── Worker card (in project) ── */
.w-card {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    margin-bottom: 5px;
    font-size: 11px;
}
.w-card-head {
    display: flex;
    align-items: center;
    padding: 5px 7px;
    background: #dfe6e9;
    border-radius: 4px 4px 0 0;
    border-bottom: 1px solid #dee2e6;
    cursor: grab;
    gap: 5px;
}
.w-card-head:active { cursor: grabbing; }
.w-name { flex: 1; font-weight: bold; font-size: 12px; color: #2c3e50; }
.w-saved { display: none; font-size: 10px; color: #27ae60; }
.btn-rm-worker {
    background: none;
    border: none;
    color: #e74c3c;
    font-size: 16px;
    line-height: 1;
    padding: 0 2px;
    cursor: pointer;
}
.btn-rm-worker:hover { color: #c0392b; }

.w-card-body { padding: 6px; }
.w-card-body .lbl { font-size: 10px; color: #7f8c8d; margin-bottom: 1px; display: block; }
.w-card-body .form-control {
    padding: 2px 5px;
    height: 24px;
    font-size: 11px;
    border-radius: 3px;
}
.w-card-body textarea.form-control { height: auto; resize: none; }
.w-row { display: flex; gap: 4px; margin-bottom: 4px; }
.w-row > div { flex: 1; }

/* ── Equipment drop zone (inside worker card) ── */
.e-drop-zone {
    min-height: 26px;
    border: 1px dashed #27ae60;
    border-radius: 3px;
    padding: 3px;
    margin-top: 4px;
    background: rgba(39,174,96,.03);
    transition: background .15s;
}
.e-drop-zone.over { background: rgba(39,174,96,.12); }
.e-drop-hint { color: #bbb; font-size: 10px; text-align: center; padding: 3px; pointer-events: none; }

/* ── Equipment badge (inside worker card) ── */
.e-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 5px;
    background: #27ae60;
    color: #fff;
    border-radius: 3px;
    font-size: 10px;
    margin: 1px;
    cursor: grab;
    user-select: none;
}
.e-badge.sortable-ghost { opacity: .35; }
.e-badge .rm-eq {
    cursor: pointer;
    opacity: .7;
    font-size: 12px;
    line-height: 1;
}
.e-badge .rm-eq:hover { opacity: 1; }

/* ── Project chips (pool) ── */
.pool-box-title.purple { background: #8e44ad; }
.p-chip {
    display: flex;
    align-items: center;
    gap: 5px;
    padding: 5px 7px;
    margin: 2px 0;
    background: #8e44ad;
    color: #fff;
    border-radius: 4px;
    font-size: 12px;
    cursor: grab;
    user-select: none;
    transition: background .15s;
}
.p-chip:hover { background: #7d3c98; }
.p-chip span { flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.btn-add-proj {
    background: rgba(255,255,255,.2);
    border: none;
    color: #fff;
    font-size: 16px;
    line-height: 1;
    padding: 0 5px;
    border-radius: 3px;
    cursor: pointer;
    flex-shrink: 0;
}
.btn-add-proj:hover { background: rgba(255,255,255,.4); }

/* ── Remove project button (in column header) ── */
.btn-rm-proj {
    background: rgba(255,255,255,.15);
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
    font-size: 14px;
    line-height: 1;
    padding: 1px 6px;
    border-radius: 3px;
    cursor: pointer;
    flex-shrink: 0;
}
.btn-rm-proj:hover { background: #e74c3c; border-color: #e74c3c; }

/* ── WO badge (in column header) ── */
.wo-badge {
    display: inline-block;
    margin-top: 4px;
    font-size: 10px;
    color: #f9ca24;
    font-weight: bold;
}
.wo-badge a { color: #f9ca24; text-decoration: underline; }
.wo-badge a:hover { color: #fff; }

/* ── Send button (in column header) ── */
.btn-send-proj {
    background: #27ae60;
    border: 1px solid rgba(255,255,255,.3);
    color: #fff;
    font-size: 11px;
    line-height: 1;
    padding: 2px 6px;
    border-radius: 3px;
    cursor: pointer;
    flex-shrink: 0;
    white-space: nowrap;
}
.btn-send-proj:hover { background: #1e8449; }

/* ── Board drag-over highlight ── */
#pb-board.board-drop-target {
    background: rgba(142,68,173,.06);
    box-shadow: inset 0 0 0 3px rgba(142,68,173,.35);
}

/* ── Materials section ── */
.mat-section {
    border-top: 1px solid #c3e6cb;
    padding: 6px;
    background: #f0fff4;
    border-radius: 0 0 6px 6px;
}
.mat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 10px;
    font-weight: bold;
    color: #1e8449;
    letter-spacing: .5px;
    margin-bottom: 4px;
}
.btn-add-mat {
    background: #27ae60;
    border: none;
    color: #fff;
    font-size: 14px;
    line-height: 1;
    padding: 0 6px;
    border-radius: 3px;
    cursor: pointer;
}
.btn-add-mat:hover { background: #1e8449; }
.mat-row {
    background: #fff;
    border: 1px solid #c3e6cb;
    border-radius: 3px;
    padding: 4px 6px;
    margin-bottom: 3px;
    font-size: 11px;
}
.mat-row-head { display: flex; align-items: center; gap: 4px; }
.mat-name { flex: 1; font-weight: bold; color: #1e8449; font-size: 11px; }
.mat-saved { display: none; font-size: 10px; color: #27ae60; }
.btn-rm-mat {
    background: none;
    border: none;
    color: #e74c3c;
    font-size: 14px;
    line-height: 1;
    padding: 0 2px;
    cursor: pointer;
}
.btn-rm-mat:hover { color: #c0392b; }
.mat-fields { display: flex; gap: 3px; margin-top: 3px; }
.mat-fields .form-control { padding: 1px 4px; height: 22px; font-size: 10px; border-radius: 2px; }
.mat-add-form {
    background: #fff;
    border: 1px dashed #27ae60;
    border-radius: 3px;
    padding: 6px;
    margin-top: 4px;
}
.mat-add-form .form-control { margin-bottom: 3px; padding: 2px 5px; height: 24px; font-size: 11px; border-radius: 3px; }
.mat-add-form textarea.form-control { height: auto; }
.mat-add-btns { display: flex; gap: 4px; margin-top: 4px; }
.mat-empty { color: #aaa; font-size: 10px; padding: 2px; }

/* ── Subcontractor section ── */
.sub-section {
    border-top: 1px solid #b8daff;
    padding: 6px;
    background: #f0f4ff;
    border-radius: 0 0 6px 6px;
}
.sub-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 10px;
    font-weight: bold;
    color: #1a5276;
    letter-spacing: .5px;
    margin-bottom: 4px;
}
.btn-add-sub {
    background: #2980b9;
    border: none;
    color: #fff;
    font-size: 14px;
    line-height: 1;
    padding: 0 6px;
    border-radius: 3px;
    cursor: pointer;
}
.btn-add-sub:hover { background: #1a5276; }
.sub-row {
    background: #fff;
    border: 1px solid #b8daff;
    border-radius: 3px;
    padding: 4px 6px;
    margin-bottom: 3px;
    font-size: 11px;
}
.sub-row-head { display: flex; align-items: center; gap: 4px; }
.sub-name { flex: 1; font-weight: bold; color: #1a5276; font-size: 11px; }
.sub-saved { display: none; font-size: 10px; color: #27ae60; }
.btn-rm-sub {
    background: none;
    border: none;
    color: #e74c3c;
    font-size: 14px;
    line-height: 1;
    padding: 0 2px;
    cursor: pointer;
}
.btn-rm-sub:hover { color: #c0392b; }
.sub-fields { display: flex; gap: 3px; margin-top: 3px; flex-wrap: wrap; }
.sub-fields .form-control { padding: 1px 4px; height: 22px; font-size: 10px; border-radius: 2px; }
.sub-add-form {
    background: #fff;
    border: 1px dashed #2980b9;
    border-radius: 3px;
    padding: 6px;
    margin-top: 4px;
}
.sub-add-form .form-control { margin-bottom: 3px; padding: 2px 5px; height: 24px; font-size: 11px; border-radius: 3px; }
.sub-add-form textarea.form-control { height: auto; }
.sub-add-btns { display: flex; gap: 4px; margin-top: 4px; }
.sub-empty { color: #aaa; font-size: 10px; padding: 2px; }
.sub-hauling-warn {
    font-size: 10px;
    color: #c0392b;
    background: #fdf2f8;
    border: 1px solid #f1948a;
    border-radius: 3px;
    padding: 3px 5px;
    margin-bottom: 3px;
    display: none;
}

/* ── Loading overlay ── */
#pb-loading {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.35);
    z-index: 9999;
    justify-content: center;
    align-items: center;
}
#pb-loading > div {
    background: #fff;
    padding: 18px 24px;
    border-radius: 6px;
    font-size: 14px;
    color: #555;
}
</style>

<div id="pb-loading">
    <div><i class="fa fa-spinner fa-spin"></i> Loading...</div>
</div>

<div id="pb-wrap">

    <!-- Top bar -->
    <div id="pb-topbar">
        <a href="javascript:history.back()" class="btn btn-default btn-sm" title="Back">
            <i class="fa fa-arrow-left"></i>
        </a>
        <h4><i class="fa fa-calendar"></i> PLANNING BOARD</h4>
        <input type="date" id="pb-date" class="form-control input-sm">
        <button id="pb-load" class="btn btn-primary btn-sm">
            <i class="fa fa-refresh"></i> Load
        </button>
        <span class="status" id="pb-status"></span>
    </div>

    <!-- Main -->
    <div id="pb-main">

        <!-- Sidebar -->
        <div id="pb-sidebar">

            <div class="pool-box">
                <div class="pool-box-title purple" data-wrap="pp-wrap">
                    <span class="pool-title-left">
                        <i class="fa fa-briefcase"></i> PROJECTS
                        <span class="pool-badge" id="proj-count">0</span>
                    </span>
                    <i class="fa fa-chevron-right pool-chevron"></i>
                </div>
                <div id="pp-wrap" style="display:none">
                    <div class="pool-search-wrap">
                        <input type="text" class="pool-search" id="proj-search" placeholder="Search project...">
                    </div>
                    <div id="project-pool" class="pool-list">
                        <div class="pool-empty">Select a date...</div>
                    </div>
                </div>
            </div>

            <div class="pool-box">
                <div class="pool-box-title" data-wrap="wp-wrap">
                    <span class="pool-title-left">
                        <i class="fa fa-users"></i> PERSONNEL
                        <span class="pool-badge" id="worker-count">0</span>
                    </span>
                    <i class="fa fa-chevron-right pool-chevron"></i>
                </div>
                <div id="wp-wrap" style="display:none">
                    <div class="pool-search-wrap">
                        <input type="text" class="pool-search" id="worker-search" placeholder="Search worker...">
                    </div>
                    <div id="worker-pool" class="pool-list">
                        <div class="pool-empty">Select a date...</div>
                    </div>
                </div>
            </div>

            <div class="pool-box">
                <div class="pool-box-title green" data-wrap="ep-wrap">
                    <span class="pool-title-left">
                        <i class="fa fa-truck"></i> EQUIPMENT
                        <span class="pool-badge" id="equip-count">0</span>
                    </span>
                    <i class="fa fa-chevron-right pool-chevron"></i>
                </div>
                <div id="ep-wrap" style="display:none">
                    <div class="pool-search-wrap">
                        <input type="text" class="pool-search" id="equip-search" placeholder="Search equipment...">
                    </div>
                    <div id="equip-pool" class="pool-list">
                        <div class="pool-empty">-</div>
                    </div>
                </div>
            </div>

            <div class="pool-box">
                <div class="pool-box-title red" data-wrap="do-wrap">
                    <span class="pool-title-left">
                        <i class="fa fa-calendar-times-o"></i> DAY OFF
                        <span class="pool-badge" id="dayoff-count">0</span>
                    </span>
                    <i class="fa fa-chevron-right pool-chevron open"></i>
                </div>
                <div id="do-wrap">
                    <div id="dayoff-pool" class="pool-list">
                        <div class="pool-empty">-</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Board -->
        <div id="pb-board">
            <div id="pb-no-data">
                <i class="fa fa-arrow-left"></i> Select a date and click <strong>Load</strong>.
            </div>
        </div>

    </div><!-- /pb-main -->
</div><!-- /pb-wrap -->

<script>
/* ================================================================
   PLANNER BOARD
   ================================================================ */
const PB = {

    state: {
        date: null,
        projects: [],
        projectPool: [],
        availableWorkers: [],
        dayoffWorkers: [],
        equipmentPool: [],
        hours: [],
        employeeTypes: [],
        materialCatalog: [],
        companyCatalog: [],
    },

    sortWorkerPool:   null,
    sortEquipPool:    null,
    sortProjectZones: [],
    sortEquipZones:   [],
    _draggingJobId:   null,

    /* ── Init ─────────────────────────────────────────────────── */
    init() {
        const today = new Date().toISOString().split('T')[0];
        $('#pb-date').val(today);
        $('#pb-load').on('click', () => this.load($('#pb-date').val()));
        this.initBoardDrop();
        this.load(today);
    },

    /* ── Load daily plan ──────────────────────────────────────── */
    load(date) {
        if (!date) return;
        $('#pb-loading').css('display', 'flex');
        $('#pb-status').text('');

        $.post(base_url + 'planner/get_daily_plan', { date })
            .done(data => {
                this.state = {
                    date,
                    projects:         data.projects          || [],
                    projectPool:      data.project_pool      || [],
                    availableWorkers: data.available_workers  || [],
                    dayoffWorkers:    data.dayoff_workers     || [],
                    equipmentPool:    data.equipment_pool     || [],
                    hours:            data.hours              || [],
                    employeeTypes:    data.employee_types     || [],
                    materialCatalog:  data.material_catalog   || [],
                    companyCatalog:   data.company_catalog    || [],
                };
                this.renderAll();
                this.initSortables();
                const n = data.projects.length;
                $('#pb-status').text(n + ' project' + (n !== 1 ? 's' : '') + ' on this date');
            })
            .fail(() => alert('Error loading the plan. Please try again.'))
            .always(() => $('#pb-loading').hide());
    },

    /* ── Render ───────────────────────────────────────────────── */
    renderAll() {
        this.renderProjectPool();
        this.renderWorkerPool();
        this.renderEquipPool();
        this.renderDayoff();
        this.renderProjects();
    },

    renderProjectPool() {
        const el    = document.getElementById('project-pool');
        const count = this.state.projectPool.length;
        document.getElementById('proj-count').textContent = count;
        document.getElementById('proj-search').value = '';
        if (!count) {
            el.innerHTML = '<div class="pool-empty">All projects already added</div>';
            return;
        }
        el.innerHTML = this.state.projectPool.map(j => this.pChipHtml(j)).join('');
        this.setupProjectDrag();
    },

    renderWorkerPool() {
        const el    = document.getElementById('worker-pool');
        const count = this.state.availableWorkers.length;
        document.getElementById('worker-count').textContent = count;
        document.getElementById('worker-search').value = '';
        if (!count) {
            el.innerHTML = '<div class="pool-empty">No workers available</div>';
            return;
        }
        el.innerHTML = this.state.availableWorkers.map(w => this.wChipHtml(w)).join('');
    },

    renderEquipPool() {
        const el    = document.getElementById('equip-pool');
        const count = this.state.equipmentPool.length;
        document.getElementById('equip-count').textContent = count;
        document.getElementById('equip-search').value = '';
        if (!count) {
            el.innerHTML = '<div class="pool-empty">No equipment available</div>';
            return;
        }
        el.innerHTML = this.state.equipmentPool.map(e => this.eChipHtml(e)).join('');
    },

    renderDayoff() {
        const el    = document.getElementById('dayoff-pool');
        const count = this.state.dayoffWorkers.length;
        document.getElementById('dayoff-count').textContent = count;
        if (!count) {
            el.innerHTML = '<div class="pool-empty">None</div>';
            return;
        }
        el.innerHTML = this.state.dayoffWorkers
            .map(w => `<div class="do-chip"><i class="fa fa-user"></i> ${this.esc(w.name)}</div>`)
            .join('');
    },

    renderProjects() {
        const board = document.getElementById('pb-board');
        if (!this.state.projects.length) {
            board.innerHTML = '<div id="pb-no-data">No projects scheduled for this date.</div>';
            return;
        }
        board.innerHTML = this.state.projects.map(p => this.projColHtml(p)).join('');
    },

    /* ── HTML Builders ────────────────────────────────────────── */
    pChipHtml(j) {
        return `<div class="p-chip" draggable="true"
            data-job-id="${j.id_job}"
            data-job-desc="${this.esc(j.job_description)}">
            <i class="fa fa-briefcase" style="font-size:10px;opacity:.7;flex-shrink:0"></i>
            <span>${this.esc(j.job_description)}</span>
            <button class="btn-add-proj" onclick="PB.addProjectToBoard(this.closest('.p-chip'))" title="Add to plan">+</button>
        </div>`;
    },

    wChipHtml(w) {
        return `<div class="w-chip" data-user-id="${w.id_user}" data-user-name="${this.esc(w.name)}">
            <i class="fa fa-user" style="font-size:10px;opacity:.7"></i>${this.esc(w.name)}
        </div>`;
    },

    eChipHtml(e) {
        return `<div class="e-chip" data-equip-id="${e.id}" data-equip-label="${this.esc(e.label)}">
            <i class="fa fa-truck" style="font-size:9px;opacity:.8"></i>${this.esc(e.label)}
        </div>`;
    },

    projColHtml(proj) {
        const pid   = proj.id_programming;
        const wHtml = proj.workers.length
            ? proj.workers.map(w => this.wCardHtml(w, pid)).join('')
            : '<div class="w-drop-hint">Drop workers here</div>';

        return `<div class="proj-col" data-job-id="${proj.fk_id_job}" data-job-desc="${this.esc(proj.job_description)}">
            <div class="proj-header">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:6px;">
                    <div style="flex:1;min-width:0;">
                        <div class="proj-name"><i class="fa fa-briefcase"></i> ${this.esc(proj.job_description)}</div>
                        <textarea class="obs-edit-area obsf" data-programming-id="${pid}" rows="2" placeholder="Observation...">${this.esc(proj.observation || '')}</textarea>
                        <span class="obs-saved" id="obs-saved-${pid}"><i class="fa fa-check"></i> saved</span>
                        <div id="wo-badge-${pid}" class="wo-badge">${proj.fk_id_workorder ? this.woBadgeHtml(proj.fk_id_workorder) : ''}</div>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:3px;align-items:stretch;">
                        <button class="btn-rm-proj" onclick="PB.removeProject(this.closest('.proj-col'))" title="Remove from plan">×</button>
                        <button class="btn-send-proj" onclick="PB.sendPlan(${pid})" title="Send SMS &amp; create WO"><i class="fa fa-send"></i> Send</button>
                    </div>
                </div>
            </div>
            <div class="w-drop-zone" data-programming-id="${proj.id_programming}">
                ${wHtml}
            </div>
            ${this.materialSectionHtml(proj)}
            ${this.subSectionHtml(proj)}
        </div>`;
    },

    materialSectionHtml(proj) {
        const pid   = proj.id_programming;
        const mats  = proj.materials || [];
        const listHtml = mats.length
            ? mats.map(m => this.materialRowHtml(m)).join('')
            : '<div class="mat-empty">No materials</div>';

        const catOpts = this.state.materialCatalog.map(c =>
            `<option value="${c.id_material}">${this.esc(c.material)}</option>`
        ).join('');

        return `<div class="mat-section">
            <div class="mat-header">
                <span><i class="fa fa-tint"></i> MATERIALS</span>
                <button class="btn-add-mat" onclick="PB.toggleAddMaterial(${pid})" title="Add material">+</button>
            </div>
            <div id="mat-list-${pid}">${listHtml}</div>
            <div id="mat-form-${pid}" class="mat-add-form" style="display:none">
                <select id="mat-sel-${pid}" class="form-control">
                    <option value="">Select material...</option>
                    ${catOpts}
                </select>
                <div style="display:flex;gap:3px;">
                    <input type="text" id="mat-qty-${pid}" class="form-control" placeholder="Qty" style="flex:1">
                    <input type="text" id="mat-unit-${pid}" class="form-control" placeholder="Unit" style="flex:1">
                </div>
                <textarea id="mat-desc-${pid}" class="form-control" placeholder="Description (optional)" rows="2" style="height:auto;margin-top:3px;resize:none;"></textarea>
                <div class="mat-add-btns">
                    <button class="btn btn-success btn-xs" onclick="PB.saveMaterial(${pid})"><i class="fa fa-save"></i> Save</button>
                    <button class="btn btn-default btn-xs" onclick="PB.toggleAddMaterial(${pid})">Cancel</button>
                </div>
            </div>
        </div>`;
    },

    materialRowHtml(m) {
        const mid = m.id_programming_material;
        return `<div class="mat-row" data-mat-id="${mid}">
            <div class="mat-row-head">
                <span class="mat-name"><i class="fa fa-tint" style="font-size:9px;opacity:.7"></i> ${this.esc(m.material)}</span>
                <span class="mat-saved"><i class="fa fa-check"></i> saved</span>
                <button class="btn-rm-mat" onclick="PB.deleteMaterial(this.closest('.mat-row'))" title="Delete">×</button>
            </div>
            <div class="mat-fields">
                <input type="text" class="form-control mf" data-mat-id="${mid}" data-field="quantity" value="${this.esc(m.quantity)}" placeholder="Qty" title="Quantity" style="flex:1">
                <input type="text" class="form-control mf" data-mat-id="${mid}" data-field="unit" value="${this.esc(m.unit)}" placeholder="Unit" title="Unit" style="flex:1">
            </div>
            ${m.description ? `<div style="font-size:10px;color:#555;margin-top:2px;">${this.esc(m.description)}</div>` : ''}
        </div>`;
    },

    toggleAddMaterial(pid) {
        const form = document.getElementById('mat-form-' + pid);
        if (!form) return;
        if (form.style.display === 'none') {
            form.style.display = 'block';
            const sel = document.getElementById('mat-sel-' + pid);
            if (sel) sel.focus();
        } else {
            form.style.display = 'none';
            this._clearMatForm(pid);
        }
    },

    _clearMatForm(pid) {
        ['mat-sel-', 'mat-qty-', 'mat-unit-', 'mat-desc-'].forEach(prefix => {
            const el = document.getElementById(prefix + pid);
            if (el) el.value = '';
        });
    },

    saveMaterial(pid) {
        const fkMaterial  = document.getElementById('mat-sel-'  + pid)?.value;
        const quantity    = (document.getElementById('mat-qty-'  + pid)?.value || '').trim();
        const unit        = (document.getElementById('mat-unit-' + pid)?.value || '').trim();
        const description = (document.getElementById('mat-desc-' + pid)?.value || '').trim();

        if (!fkMaterial || !quantity || !unit) {
            alert('Material, quantity and unit are required.');
            return;
        }

        $.post(base_url + 'planner/planner_save_material', {
            id_programming: pid,
            fk_id_material: fkMaterial,
            quantity,
            unit,
            description,
        }).done(resp => {
            if (resp.status === 'success') {
                const list  = document.getElementById('mat-list-' + pid);
                const empty = list?.querySelector('.mat-empty');
                if (empty) empty.remove();
                list?.insertAdjacentHTML('beforeend', this.materialRowHtml(resp.material));
                this.toggleAddMaterial(pid);
            } else {
                alert('Error saving material.');
            }
        }).fail(() => alert('Network error. Please try again.'));
    },

    deleteMaterial(row) {
        if (!row) return;
        const matId = parseInt(row.dataset.matId);
        if (!matId || !confirm('Delete this material?')) return;

        $.post(base_url + 'planner/planner_delete_material', { id_programming_material: matId })
            .done(resp => {
                if (resp.status === 'success') {
                    const list = row.closest('[id^="mat-list-"]');
                    row.remove();
                    if (list && !list.querySelector('.mat-row')) {
                        list.innerHTML = '<div class="mat-empty">No materials</div>';
                    }
                } else {
                    alert('Error deleting material.');
                }
            });
    },

    /* ── Subcontractor section ───────────────────────────────── */
    subSectionHtml(proj) {
        const pid  = proj.id_programming;
        const subs = proj.occasional || [];
        const listHtml = subs.length
            ? subs.map(s => this.subRowHtml(s)).join('')
            : '<div class="sub-empty">No subcontractors</div>';

        const compOpts = this.state.companyCatalog.map(c =>
            `<option value="${c.id_company}" data-hauling="${c.does_hauling}">${this.esc(c.company_name)}</option>`
        ).join('');

        return `<div class="sub-section">
            <div class="sub-header">
                <span><i class="fa fa-beer"></i> SUBCONTRACTOR</span>
                <button class="btn-add-sub" onclick="PB.toggleAddSub(${pid})" title="Add subcontractor">+</button>
            </div>
            <div id="sub-list-${pid}">${listHtml}</div>
            <div id="sub-form-${pid}" class="sub-add-form" style="display:none">
                <select id="sub-co-${pid}" class="form-control" onchange="PB.onSubCompanyChange(${pid})">
                    <option value="">Select company...</option>
                    ${compOpts}
                </select>
                <div id="sub-haul-warn-${pid}" class="sub-hauling-warn">
                    <i class="fa fa-warning"></i> This company provides hauling. Quantity will create that many hauling cards.
                </div>
                <textarea id="sub-equip-${pid}" class="form-control" placeholder="Equipment *" rows="2" style="height:auto;"></textarea>
                <div style="display:flex;gap:3px;">
                    <input type="text" id="sub-qty-${pid}"  class="form-control" placeholder="Qty *"     style="flex:1">
                    <input type="text" id="sub-unit-${pid}" class="form-control" placeholder="Unit *"    style="flex:1">
                    <input type="text" id="sub-hrs-${pid}"  class="form-control" placeholder="Hours"     style="flex:1">
                </div>
                <input type="text"     id="sub-cont-${pid}" class="form-control" placeholder="Field Contact *" style="margin-top:3px;">
                <textarea id="sub-desc-${pid}" class="form-control" placeholder="Description (optional)" rows="2" style="height:auto;margin-top:3px;resize:none;"></textarea>
                <div class="sub-add-btns">
                    <button class="btn btn-primary btn-xs" onclick="PB.saveSub(${pid})"><i class="fa fa-save"></i> Save</button>
                    <button class="btn btn-default btn-xs" onclick="PB.toggleAddSub(${pid})">Cancel</button>
                </div>
            </div>
        </div>`;
    },

    subRowHtml(s) {
        const sid = s.id_programming_ocasional;
        return `<div class="sub-row" data-sub-id="${sid}">
            <div class="sub-row-head">
                <span class="sub-name"><i class="fa fa-beer" style="font-size:9px;opacity:.7"></i> ${this.esc(s.company_name)}</span>
                <span class="sub-saved"><i class="fa fa-check"></i> saved</span>
                <button class="btn-rm-sub" onclick="PB.deleteSub(this.closest('.sub-row'))" title="Delete">×</button>
            </div>
            <div style="margin-top:3px;">
                <input type="text" class="form-control sf" data-sub-id="${sid}" data-field="equipment" value="${this.esc(s.equipment)}" placeholder="Equipment" title="Equipment" style="width:100%;padding:1px 4px;height:22px;font-size:10px;border-radius:2px;">
            </div>
            <div class="sub-fields">
                <input type="text" class="form-control sf" data-sub-id="${sid}" data-field="quantity"    value="${this.esc(s.quantity)}"    placeholder="Qty"     title="Quantity"      style="flex:1">
                <input type="text" class="form-control sf" data-sub-id="${sid}" data-field="unit"        value="${this.esc(s.unit)}"        placeholder="Unit"    title="Unit"          style="flex:1">
                <input type="text" class="form-control sf" data-sub-id="${sid}" data-field="hours"       value="${this.esc(s.hours)}"       placeholder="Hrs"     title="Hours"         style="flex:1">
                <input type="text" class="form-control sf" data-sub-id="${sid}" data-field="contact"     value="${this.esc(s.contact)}"     placeholder="Contact" title="Field Contact" style="flex:2">
            </div>
        </div>`;
    },

    onSubCompanyChange(pid) {
        const sel  = document.getElementById('sub-co-' + pid);
        const warn = document.getElementById('sub-haul-warn-' + pid);
        if (!sel || !warn) return;
        const hauling = sel.options[sel.selectedIndex]?.dataset.hauling;
        warn.style.display = hauling == 1 ? 'block' : 'none';
    },

    toggleAddSub(pid) {
        const form = document.getElementById('sub-form-' + pid);
        if (!form) return;
        if (form.style.display === 'none') {
            form.style.display = 'block';
            const sel = document.getElementById('sub-co-' + pid);
            if (sel) sel.focus();
        } else {
            form.style.display = 'none';
            this._clearSubForm(pid);
        }
    },

    _clearSubForm(pid) {
        ['sub-co-', 'sub-equip-', 'sub-qty-', 'sub-unit-', 'sub-hrs-', 'sub-cont-', 'sub-desc-'].forEach(prefix => {
            const el = document.getElementById(prefix + pid);
            if (el) el.value = '';
        });
        const warn = document.getElementById('sub-haul-warn-' + pid);
        if (warn) warn.style.display = 'none';
    },

    saveSub(pid) {
        const fkCompany   = document.getElementById('sub-co-'    + pid)?.value;
        const equipment   = (document.getElementById('sub-equip-' + pid)?.value || '').trim();
        const quantity    = (document.getElementById('sub-qty-'   + pid)?.value || '').trim();
        const unit        = (document.getElementById('sub-unit-'  + pid)?.value || '').trim();
        const hours       = (document.getElementById('sub-hrs-'   + pid)?.value || '').trim();
        const contact     = (document.getElementById('sub-cont-'  + pid)?.value || '').trim();
        const description = (document.getElementById('sub-desc-'  + pid)?.value || '').trim();

        if (!fkCompany || !equipment || !quantity || !unit || !contact) {
            alert('Company, equipment, quantity, unit and contact are required.');
            return;
        }

        $.post(base_url + 'planner/planner_save_subcontractor', {
            id_programming: pid,
            fk_id_company:  fkCompany,
            equipment,
            quantity,
            unit,
            hours,
            contact,
            description,
        }).done(resp => {
            if (resp.status === 'success') {
                const list  = document.getElementById('sub-list-' + pid);
                const empty = list?.querySelector('.sub-empty');
                if (empty) empty.remove();
                resp.subcontractors.forEach(s => list?.insertAdjacentHTML('beforeend', this.subRowHtml(s)));
                this.toggleAddSub(pid);
            } else {
                alert('Error saving subcontractor.');
            }
        }).fail(() => alert('Network error. Please try again.'));
    },

    deleteSub(row) {
        if (!row) return;
        const subId = parseInt(row.dataset.subId);
        if (!subId || !confirm('Delete this subcontractor?')) return;

        $.post(base_url + 'planner/planner_delete_subcontractor', { id_programming_ocasional: subId })
            .done(resp => {
                if (resp.status === 'success') {
                    const list = row.closest('[id^="sub-list-"]');
                    row.remove();
                    if (list && !list.querySelector('.sub-row')) {
                        list.innerHTML = '<div class="sub-empty">No subcontractors</div>';
                    }
                } else {
                    alert('Error deleting subcontractor.');
                }
            });
    },

    wCardHtml(w, programmingId) {
        const hoursOpts = this.state.hours.map(h =>
            `<option value="${h.id_hora}" ${h.id_hora == w.fk_id_hour ? 'selected' : ''}>${h.hora}</option>`
        ).join('');

        const equipHtml = (w.machine_items && w.machine_items.length)
            ? w.machine_items.map(m => this.eBadgeHtml(m.id, m.label)).join('')
            : '<div class="e-drop-hint">Drag equipment here</div>';

        const machineIdsJson = JSON.stringify(w.machine_ids || []);

        return `<div class="w-card"
            data-user-id="${w.id_user}"
            data-user-name="${this.esc(w.name)}"
            data-pw-id="${w.id_programming_worker}"
            data-programming-id="${programmingId}"
            data-machine-ids='${machineIdsJson}'>
            ${this.wCardInner(w, hoursOpts, equipHtml)}
        </div>`;
    },

    wCardInner(w, hoursOpts, equipHtml) {
        if (!hoursOpts) {
            hoursOpts = this.state.hours.map(h =>
                `<option value="${h.id_hora}" ${h.id_hora == (w.fk_id_hour || 15) ? 'selected' : ''}>${h.hora}</option>`
            ).join('');
        }
        if (equipHtml === undefined) {
            equipHtml = '<div class="e-drop-hint">Drag equipment here</div>';
        }

        const pwId = w.id_programming_worker;

        const empTypeOpts = this.state.employeeTypes.map(t =>
            `<option value="${t.id_employee_type}" ${t.id_employee_type == w.fk_id_employee_type ? 'selected' : ''}>${this.esc(t.employee_type)}</option>`
        ).join('');

        return `<div class="w-card-head">
            <span class="w-name"><i class="fa fa-user" style="font-size:10px;opacity:.6"></i> ${this.esc(w.name)}</span>
            <span class="w-saved"><i class="fa fa-check"></i> saved</span>
            <button class="btn-rm-worker" onclick="PB.removeWorker(this.closest('.w-card'))" title="Remove worker">×</button>
        </div>
        <div class="w-card-body">
            <div style="margin-bottom:4px;">
                <span class="lbl">Employee Type</span>
                <select class="form-control wf" data-field="fk_id_employee_type">${empTypeOpts}</select>
            </div>
            <div class="w-row">
                <div>
                    <span class="lbl">Time In</span>
                    <select class="form-control wf" data-field="fk_id_hour">${hoursOpts}</select>
                </div>
                <div>
                    <span class="lbl">Site</span>
                    <select class="form-control wf" data-field="site">
                        <option value="1" ${w.site==1?'selected':''}>Yard</option>
                        <option value="2" ${w.site==2?'selected':''}>Site</option>
                        <option value="3" ${w.site==3?'selected':''}>Terminal</option>
                        <option value="4" ${w.site==4?'selected':''}>Online</option>
                        <option value="5" ${w.site==5?'selected':''}>Training</option>
                        <option value="6" ${w.site==6?'selected':''}>Client</option>
                    </select>
                </div>
            </div>
            <div class="w-row">
                <div>
                    <span class="lbl">Safety</span>
                    <select class="form-control wf" data-field="safety">
                        <option value="" ${!w.safety?'selected':''}>-</option>
                        <option value="1" ${w.safety==1?'selected':''}>FLHA</option>
                        <option value="2" ${w.safety==2?'selected':''}>IHSR</option>
                        <option value="3" ${w.safety==3?'selected':''}>JSO</option>
                    </select>
                </div>
                <div>
                    <span class="lbl">Create WO</span>
                    <select class="form-control wf" data-field="creat_wo">
                        <option value="" ${!w.creat_wo?'selected':''}>-</option>
                        <option value="1" ${w.creat_wo==1?'selected':''}>Yes</option>
                        <option value="2" ${w.creat_wo==2?'selected':''}>No</option>
                    </select>
                </div>
            </div>
            <div style="margin-bottom:4px;">
                <span class="lbl">Description</span>
                <textarea class="form-control wf" data-field="description" rows="2">${this.esc(w.description || '')}</textarea>
            </div>
            <div>
                <span class="lbl">Equipment <small style="color:#bbb;">(drag from pool)</small></span>
                <div class="e-drop-zone" data-pw-id="${pwId}">${equipHtml}</div>
            </div>
        </div>`;
    },

    woBadgeHtml(woId) {
        return `<i class="fa fa-file-text-o"></i> <a href="${base_url}workorders/add_workorder/${woId}" target="_blank">W.O. #${woId}</a>`;
    },

    eBadgeHtml(equipId, equipLabel) {
        return `<div class="e-badge" data-equip-id="${equipId}" data-equip-label="${this.esc(equipLabel)}">
            <i class="fa fa-truck" style="font-size:9px;"></i>${this.esc(equipLabel)}<span class="rm-eq" onclick="PB.removeEquip(this)">×</span>
        </div>`;
    },

    /* ── Sortable initialization ──────────────────────────────── */
    initSortables() {
        // Destroy existing
        if (this.sortWorkerPool) this.sortWorkerPool.destroy();
        if (this.sortEquipPool)  this.sortEquipPool.destroy();
        this.sortProjectZones.forEach(s => s.destroy());
        this.sortEquipZones.forEach(s => s.destroy());
        this.sortProjectZones = [];
        this.sortEquipZones   = [];

        this.sortWorkerPool = Sortable.create(document.getElementById('worker-pool'), {
            group: { name: 'workers', pull: true, put: true },
            animation: 150,
            onEnd: evt => this.onWorkerDrop(evt),
        });

        document.querySelectorAll('.w-drop-zone').forEach(zone => {
            const s = Sortable.create(zone, {
                group: { name: 'workers', pull: true, put: true },
                handle: '.w-card-head',
                animation: 150,
                onEnd: evt => this.onWorkerDrop(evt),
            });
            this.sortProjectZones.push(s);
        });

        this.sortEquipPool = Sortable.create(document.getElementById('equip-pool'), {
            group: { name: 'equipment', pull: true, put: true },
            animation: 150,
            onEnd: evt => this.onEquipDrop(evt),
        });

        document.querySelectorAll('.e-drop-zone').forEach(zone => {
            this.addEquipZoneSortable(zone);
        });

        this.setupProjectDrag();
    },

    addEquipZoneSortable(zone) {
        if (!zone) return;
        const s = Sortable.create(zone, {
            group: { name: 'equipment', pull: true, put: true },
            animation: 150,
            onEnd: evt => this.onEquipDrop(evt),
        });
        this.sortEquipZones.push(s);
    },

    /* ── Worker drag handler ──────────────────────────────────── */
    onWorkerDrop(evt) {
        const item  = evt.item;
        const from  = evt.from;
        const to    = evt.to;

        if (from === to) return;

        const fromPool = from.id === 'worker-pool';
        const toPool   = to.id   === 'worker-pool';
        const fromPid  = from.dataset.programmingId;
        const toPid    = to.dataset.programmingId;
        const userId   = parseInt(item.dataset.userId);
        const userName = item.dataset.userName;
        const pwId     = item.dataset.pwId ? parseInt(item.dataset.pwId) : null;

        if (fromPool && !toPool) {
            // ── Pool → Project
            $.post(base_url + 'planner/planner_save_assignment', { id_programming: toPid, id_user: userId })
                .done(resp => {
                    if (resp.status === 'success') {
                        item.dataset.pwId          = resp.id_programming_worker;
                        item.dataset.programmingId = toPid;
                        item.dataset.machineIds    = '[]';
                        item.className             = 'w-card';
                        item.innerHTML = this.wCardInner({
                            id_programming_worker: resp.id_programming_worker,
                            name: userName, fk_id_hour: 15, site: 1, safety: '', description: '', creat_wo: '', machine_ids: []
                        });
                        this.addEquipZoneSortable(item.querySelector('.e-drop-zone'));
                        this.refreshHint(to);
                    } else {
                        document.getElementById('worker-pool').appendChild(item);
                    }
                })
                .fail(() => document.getElementById('worker-pool').appendChild(item));

        } else if (!fromPool && toPool) {
            // ── Project → Pool: return equip first
            this.returnEquipFromCard(item);

            $.post(base_url + 'planner/planner_remove_assignment', { id_programming_worker: pwId, id_programming: fromPid })
                .done(resp => {
                    if (resp.status === 'success') {
                        item.dataset.machineIds = '[]';
                        item.className  = 'w-chip';
                        item.innerHTML  = `<i class="fa fa-user" style="font-size:10px;opacity:.7"></i>${this.esc(userName)}`;
                        item.removeAttribute('data-pw-id');
                        item.removeAttribute('data-programming-id');
                        this.refreshHint(from);
                    } else {
                        from.appendChild(item);
                    }
                })
                .fail(() => from.appendChild(item));

        } else if (!fromPool && !toPool && fromPid !== toPid) {
            // ── Project → Different project (equipment cleared on rebind)
            $.post(base_url + 'planner/planner_remove_assignment', { id_programming_worker: pwId, id_programming: fromPid })
                .done(() => {
                    $.post(base_url + 'planner/planner_save_assignment', { id_programming: toPid, id_user: userId })
                        .done(resp => {
                            if (resp.status === 'success') {
                                item.dataset.pwId          = resp.id_programming_worker;
                                item.dataset.programmingId = toPid;
                                item.dataset.machineIds    = '[]';
                                // Update equip zone pw-id and clear equipment
                                const ez = item.querySelector('.e-drop-zone');
                                if (ez) {
                                    ez.dataset.pwId = resp.id_programming_worker;
                                    ez.innerHTML    = '<div class="e-drop-hint">Drag equipment here</div>';
                                }
                                this.refreshHint(from);
                                this.refreshHint(to);
                            } else {
                                from.appendChild(item);
                            }
                        });
                })
                .fail(() => from.appendChild(item));
        }
    },

    /* ── Equipment drag handler ───────────────────────────────── */
    onEquipDrop(evt) {
        const item     = evt.item;
        const from     = evt.from;
        const to       = evt.to;

        if (from === to) return;

        const fromPool = from.id === 'equip-pool';
        const toPool   = to.id   === 'equip-pool';
        const equipId  = parseInt(item.dataset.equipId);
        const eLabel   = item.dataset.equipLabel || '';
        const fromPwId = from.dataset.pwId ? parseInt(from.dataset.pwId) : null;
        const toPwId   = to.dataset.pwId   ? parseInt(to.dataset.pwId)   : null;

        if (fromPool && !toPool) {
            // ── Pool → Worker equip zone
            const card       = to.closest('.w-card');
            const machineIds = JSON.parse(card.dataset.machineIds || '[]');
            machineIds.push(equipId);
            card.dataset.machineIds = JSON.stringify(machineIds);

            item.className = 'e-badge';
            item.innerHTML = `<i class="fa fa-truck" style="font-size:9px;"></i>${this.esc(eLabel)}<span class="rm-eq" onclick="PB.removeEquip(this)">×</span>`;
            this.refreshEquipHint(to);

            $.post(base_url + 'planner/planner_save_worker_detail', {
                id_programming_worker: toPwId,
                field: 'fk_id_machine',
                value: machineIds.join(',')
            }).done(resp => {
                if (resp.status === 'success') this.showSaved(card);
                else {
                    item.remove();
                    machineIds.splice(machineIds.indexOf(equipId), 1);
                    card.dataset.machineIds = JSON.stringify(machineIds);
                    document.getElementById('equip-pool').insertAdjacentHTML('beforeend', this.eChipHtml({ id: equipId, label: eLabel }));
                    this.refreshEquipHint(to);
                }
            });

        } else if (!fromPool && toPool) {
            // ── Worker equip zone → Pool
            const card       = from.closest('.w-card');
            const machineIds = JSON.parse(card.dataset.machineIds || '[]');
            const idx        = machineIds.indexOf(equipId);
            if (idx > -1) machineIds.splice(idx, 1);
            card.dataset.machineIds = JSON.stringify(machineIds);

            item.className = 'e-chip';
            item.innerHTML = `<i class="fa fa-truck" style="font-size:9px;opacity:.8"></i>${this.esc(eLabel)}`;
            this.refreshEquipHint(from);

            $.post(base_url + 'planner/planner_save_worker_detail', {
                id_programming_worker: fromPwId,
                field: 'fk_id_machine',
                value: machineIds.join(',')
            }).done(resp => {
                if (resp.status === 'success') this.showSaved(card);
                else {
                    item.remove();
                    machineIds.push(equipId);
                    card.dataset.machineIds = JSON.stringify(machineIds);
                    from.insertAdjacentHTML('beforeend', this.eBadgeHtml(equipId, eLabel));
                }
            });

        } else if (!fromPool && !toPool && fromPwId !== toPwId) {
            // ── Between worker equip zones
            const fromCard    = from.closest('.w-card');
            const toCard      = to.closest('.w-card');
            const fromIds     = JSON.parse(fromCard.dataset.machineIds || '[]');
            const toIds       = JSON.parse(toCard.dataset.machineIds   || '[]');
            const idx         = fromIds.indexOf(equipId);
            if (idx > -1) fromIds.splice(idx, 1);
            toIds.push(equipId);
            fromCard.dataset.machineIds = JSON.stringify(fromIds);
            toCard.dataset.machineIds   = JSON.stringify(toIds);

            item.innerHTML = `<i class="fa fa-truck" style="font-size:9px;"></i>${this.esc(eLabel)}<span class="rm-eq" onclick="PB.removeEquip(this)">×</span>`;
            this.refreshEquipHint(from);
            this.refreshEquipHint(to);

            $.post(base_url + 'planner/planner_save_worker_detail', { id_programming_worker: fromPwId, field: 'fk_id_machine', value: fromIds.join(',') });
            $.post(base_url + 'planner/planner_save_worker_detail', { id_programming_worker: toPwId,   field: 'fk_id_machine', value: toIds.join(',')   })
                .done(resp => { if (resp.status === 'success') this.showSaved(toCard); });
        }
    },

    /* ── Remove worker (button) ───────────────────────────────── */
    removeWorker(card) {
        if (!card) return;
        const pwId          = parseInt(card.dataset.pwId);
        const programmingId = parseInt(card.dataset.programmingId);
        const userName      = card.dataset.userName;
        const userId        = parseInt(card.dataset.userId);
        const dropZone      = card.closest('.w-drop-zone');

        if (!pwId || !programmingId) return;

        this.returnEquipFromCard(card);

        $.post(base_url + 'planner/planner_remove_assignment', { id_programming_worker: pwId, id_programming: programmingId })
            .done(resp => {
                if (resp.status === 'success') {
                    card.className = 'w-chip';
                    card.innerHTML = `<i class="fa fa-user" style="font-size:10px;opacity:.7"></i>${this.esc(userName)}`;
                    card.dataset.userId   = userId;
                    card.dataset.userName = userName;
                    card.dataset.machineIds = '[]';
                    card.removeAttribute('data-pw-id');
                    card.removeAttribute('data-programming-id');
                    document.getElementById('worker-pool').appendChild(card);
                    this.refreshHint(dropZone);
                }
            });
    },

    /* ── Remove equipment (× button) ─────────────────────────── */
    removeEquip(btn) {
        const badge    = btn.closest('.e-badge');
        const equipId  = parseInt(badge.dataset.equipId);
        const eLabel   = badge.dataset.equipLabel || '';
        const zone     = badge.closest('.e-drop-zone');
        const pwId     = parseInt(zone.dataset.pwId);
        const card     = zone.closest('.w-card');

        const machineIds = JSON.parse(card.dataset.machineIds || '[]');
        const idx        = machineIds.indexOf(equipId);
        if (idx > -1) machineIds.splice(idx, 1);
        card.dataset.machineIds = JSON.stringify(machineIds);

        badge.remove();
        this.refreshEquipHint(zone);
        document.getElementById('equip-pool').insertAdjacentHTML('beforeend', this.eChipHtml({ id: equipId, label: eLabel }));

        $.post(base_url + 'planner/planner_save_worker_detail', {
            id_programming_worker: pwId,
            field: 'fk_id_machine',
            value: machineIds.join(',')
        }).done(resp => { if (resp.status === 'success') this.showSaved(card); });
    },

    /* ── Return all equipment from a card to pool ─────────────── */
    returnEquipFromCard(card) {
        card.querySelectorAll('.e-badge').forEach(badge => {
            const equipId = parseInt(badge.dataset.equipId);
            const eLabel  = badge.dataset.equipLabel || '';
            badge.remove();
            document.getElementById('equip-pool').insertAdjacentHTML('beforeend', this.eChipHtml({ id: equipId, label: eLabel }));
        });
    },

    /* ── Add project to board ────────────────────────────────── */
    addProjectToBoard(chip) {
        if (!chip) return;
        if (!this.state.date) { alert('Select a date first.'); return; }

        const idJob  = parseInt(chip.dataset.jobId);
        chip.style.opacity       = '.5';
        chip.style.pointerEvents = 'none';

        $.post(base_url + 'planner/planner_add_project', { id_job: idJob, date: this.state.date })
            .done(resp => {
                if (resp.status === 'success') {
                    chip.remove();
                    const board  = document.getElementById('pb-board');
                    const noData = document.getElementById('pb-no-data');
                    if (noData) noData.remove();
                    board.insertAdjacentHTML('beforeend', this.projColHtml(resp.project));
                    const newZone = board.lastElementChild.querySelector('.w-drop-zone');
                    const s = Sortable.create(newZone, {
                        group: { name: 'workers', pull: true, put: true },
                        handle: '.w-card-head',
                        animation: 150,
                        onEnd: evt => this.onWorkerDrop(evt),
                    });
                    this.sortProjectZones.push(s);
                    this.updateProjectCount();
                } else {
                    chip.style.opacity       = '';
                    chip.style.pointerEvents = '';
                    if (resp.message === 'already_exists') alert('This project is already scheduled for this day.');
                }
            })
            .fail(() => {
                chip.style.opacity       = '';
                chip.style.pointerEvents = '';
            });
    },

    /* ── Send SMS & create WO ────────────────────────────────── */
    sendPlan(pid) {
        if (!confirm('Send SMS notifications to all workers and create Work Order for this project?')) return;

        const col = document.querySelector(`.w-drop-zone[data-programming-id="${pid}"]`)?.closest('.proj-col');
        if (col) { col.style.opacity = '.6'; col.style.pointerEvents = 'none'; }

        $.post(base_url + 'planner/planner_send', { id_programming: pid })
            .done(resp => {
                if (resp.status === 'success') {
                    if (resp.wo_number) {
                        const badge = document.getElementById('wo-badge-' + pid);
                        if (badge) badge.innerHTML = this.woBadgeHtml(resp.wo_number);
                    }
                    let msg = 'Messages sent to ' + resp.workers_notified + ' worker(s).';
                    if (resp.wo_number) msg += '\nWork Order #' + resp.wo_number + ' created.';
                    alert(msg);
                } else {
                    alert('Error sending notifications. Please try again.');
                }
            })
            .fail(() => alert('Network error. Please try again.'))
            .always(() => {
                if (col) { col.style.opacity = ''; col.style.pointerEvents = ''; }
            });
    },

    /* ── Remove project from board ───────────────────────────── */
    removeProject(col) {
        if (!col) return;
        const zone          = col.querySelector('.w-drop-zone');
        const idProgramming = parseInt(zone.dataset.programmingId);
        const idJob         = parseInt(col.dataset.jobId);
        const jobDesc       = col.dataset.jobDesc || '';

        if (!confirm('Remove "' + jobDesc + '" from this day?\nAll assigned workers will be returned to the pool.')) return;

        col.style.opacity       = '.5';
        col.style.pointerEvents = 'none';

        $.post(base_url + 'planner/planner_remove_project', { id_programming: idProgramming })
            .done(resp => {
                if (resp.status === 'success') {
                    col.querySelectorAll('.w-card').forEach(card => {
                        this.returnEquipFromCard(card);
                        const userId   = parseInt(card.dataset.userId);
                        const userName = card.dataset.userName;
                        const chip     = document.createElement('div');
                        chip.className          = 'w-chip';
                        chip.dataset.userId     = userId;
                        chip.dataset.userName   = userName;
                        chip.innerHTML = `<i class="fa fa-user" style="font-size:10px;opacity:.7"></i>${this.esc(userName)}`;
                        document.getElementById('worker-pool').appendChild(chip);
                    });
                    col.remove();

                    const pool  = document.getElementById('project-pool');
                    const empty = pool.querySelector('.pool-empty');
                    if (empty) empty.remove();
                    pool.insertAdjacentHTML('beforeend', this.pChipHtml({ id_job: idJob, job_description: jobDesc }));
                    this.setupProjectDrag();
                    this.updateWorkerCount();
                    this.updateProjectCount();

                    if (!document.querySelector('.proj-col')) {
                        document.getElementById('pb-board').innerHTML =
                            '<div id="pb-no-data">No projects scheduled for this date.</div>';
                    }
                } else {
                    col.style.opacity       = '';
                    col.style.pointerEvents = '';
                    alert('Error removing project. Please try again.');
                }
            })
            .fail(() => {
                col.style.opacity       = '';
                col.style.pointerEvents = '';
                alert('Network error. Please try again.');
            });
    },

    /* ── Setup HTML5 drag on project chips ───────────────────── */
    setupProjectDrag() {
        document.querySelectorAll('.p-chip:not([data-drag-init])').forEach(chip => {
            chip.dataset.dragInit = '1';
            chip.addEventListener('dragstart', () => {
                PB._draggingJobId = chip.dataset.jobId;
                chip.style.opacity = '.5';
            });
            chip.addEventListener('dragend', () => {
                chip.style.opacity = '';
                PB._draggingJobId  = null;
            });
        });
    },

    /* ── Board drop zone for project chips (init once) ───────── */
    initBoardDrop() {
        const board = document.getElementById('pb-board');
        board.addEventListener('dragover', e => {
            if (!PB._draggingJobId) return;
            e.preventDefault();
            board.classList.add('board-drop-target');
        });
        board.addEventListener('dragleave', e => {
            if (!e.relatedTarget || !board.contains(e.relatedTarget)) {
                board.classList.remove('board-drop-target');
            }
        });
        board.addEventListener('drop', e => {
            board.classList.remove('board-drop-target');
            if (!PB._draggingJobId) return;
            e.preventDefault();
            const chip = document.querySelector(`.p-chip[data-job-id="${PB._draggingJobId}"]`);
            if (chip) PB.addProjectToBoard(chip);
        });
    },

    /* ── Badge counters ──────────────────────────────────────── */
    updateWorkerCount() {
        document.getElementById('worker-count').textContent =
            document.querySelectorAll('#worker-pool .w-chip').length;
    },

    updateProjectCount() {
        document.getElementById('proj-count').textContent =
            document.querySelectorAll('#project-pool .p-chip').length;
    },

    /* ── Helpers ──────────────────────────────────────────────── */
    refreshHint(zone) {
        if (!zone) return;
        const cards = zone.querySelectorAll('.w-card');
        const hint  = zone.querySelector('.w-drop-hint');
        if (cards.length === 0 && !hint) {
            zone.insertAdjacentHTML('beforeend', '<div class="w-drop-hint">Drop workers here</div>');
        } else if (cards.length > 0 && hint) {
            hint.remove();
        }
    },

    refreshEquipHint(zone) {
        if (!zone) return;
        const badges = zone.querySelectorAll('.e-badge');
        const hint   = zone.querySelector('.e-drop-hint');
        if (badges.length === 0 && !hint) {
            zone.insertAdjacentHTML('beforeend', '<div class="e-drop-hint">Drag equipment here</div>');
        } else if (badges.length > 0 && hint) {
            hint.remove();
        }
    },

    showSaved(card) {
        if (!card) return;
        const el = card.querySelector('.w-saved');
        if (!el) return;
        el.style.display = 'inline';
        setTimeout(() => el.style.display = 'none', 1400);
    },

    esc(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
    },
};

/* ── Auto-save on field change (delegated) ─────────────────────── */
$(document).on('change', '.wf', function() {
    const field = $(this).data('field');
    const value = $(this).val();
    const card  = $(this).closest('.w-card');
    const pwId  = card.data('pw-id');
    if (!pwId) return;

    $.post(base_url + 'planner/planner_save_worker_detail', {
        id_programming_worker: pwId,
        field,
        value
    }).done(resp => { if (resp.status === 'success') PB.showSaved(card[0]); });
});

$(document).on('input', 'textarea.wf', function() {
    clearTimeout($(this).data('t'));
    $(this).data('t', setTimeout(() => $(this).trigger('change'), 700));
});

/* ── Auto-save material fields on change ───────────────────────── */
$(document).on('change', '.mf', function() {
    const field = $(this).data('field');
    const value = $(this).val();
    const matId = $(this).data('mat-id');
    const row   = $(this).closest('.mat-row');
    if (!matId) return;

    $.post(base_url + 'planner/planner_update_material', {
        id_programming_material: matId,
        field,
        value,
    }).done(resp => {
        if (resp.status === 'success') {
            const saved = row.find('.mat-saved');
            saved.show();
            setTimeout(() => saved.hide(), 1400);
        }
    });
});

/* ── Auto-save observation on input ────────────────────────────── */
$(document).on('input', 'textarea.obsf', function() {
    clearTimeout($(this).data('t'));
    const $ta = $(this);
    $ta.data('t', setTimeout(() => {
        const pid   = $ta.data('programmingId');
        const value = $ta.val();
        $.post(base_url + 'planner/planner_save_observation', {
            id_programming: pid,
            observation:    value,
        }).done(resp => {
            if (resp.status === 'success') {
                const ind = document.getElementById('obs-saved-' + pid);
                if (ind) { ind.style.display = 'inline'; setTimeout(() => ind.style.display = 'none', 1400); }
            }
        });
    }, 700));
});

/* ── Auto-save subcontractor fields on change ──────────────────── */
$(document).on('change', '.sf', function() {
    const field = $(this).data('field');
    const value = $(this).val();
    const subId = $(this).data('sub-id');
    const row   = $(this).closest('.sub-row');
    if (!subId) return;

    $.post(base_url + 'planner/planner_update_subcontractor', {
        id_programming_ocasional: subId,
        field,
        value,
    }).done(resp => {
        if (resp.status === 'success') {
            const saved = row.find('.sub-saved');
            saved.show();
            setTimeout(() => saved.hide(), 1400);
        }
    });
});

/* ── Drag-over visual feedback ─────────────────────────────────── */
$(document).on('dragover dragenter', '.w-drop-zone', function() { $(this).addClass('over'); });
$(document).on('dragleave drop',     '.w-drop-zone', function() { $(this).removeClass('over'); });
$(document).on('dragover dragenter', '.e-drop-zone', function() { $(this).addClass('over'); });
$(document).on('dragleave drop',     '.e-drop-zone', function() { $(this).removeClass('over'); });

/* ── Sidebar collapse ──────────────────────────────────────────── */
$(document).on('click', '.pool-box-title', function() {
    const wrapId  = $(this).data('wrap');
    const chevron = $(this).find('.pool-chevron');
    $('#' + wrapId).slideToggle(160, function() {
        chevron.toggleClass('open');
    });
});

/* ── Sidebar search filter ─────────────────────────────────────── */
$(document).on('input', '#proj-search', function() {
    const q = $(this).val().toLowerCase().trim();
    $('#project-pool').children('.p-chip').each(function() {
        $(this).toggle(!q || $(this).text().toLowerCase().includes(q));
    });
});

$(document).on('input', '#worker-search', function() {
    const q = $(this).val().toLowerCase().trim();
    $('#worker-pool').children('.w-chip').each(function() {
        $(this).toggle(!q || $(this).text().toLowerCase().includes(q));
    });
});

$(document).on('input', '#equip-search', function() {
    const q = $(this).val().toLowerCase().trim();
    $('#equip-pool').children('.e-chip').each(function() {
        $(this).toggle(!q || $(this).text().toLowerCase().includes(q));
    });
});

/* ── Start ─────────────────────────────────────────────────────── */
$(function() { PB.init(); });
</script>
