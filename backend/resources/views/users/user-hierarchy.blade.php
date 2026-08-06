@include('layouts.header', [
    'breadcrumb' => ['Users Hierarchy'],
    'pageTitle'  => 'Users Hierarchy',
    'title'      => 'Users Hierarchy'
])

<style>
    body { background: #f5f7fb; }

    /* ===== ORG CHART (Top → Bottom) ===== */
    .org-chart{
        display:flex; flex-direction:column; align-items:flex-start; /* anchor left */
        margin-top:20px;
        font-family:system-ui,-apple-system,"Segoe UI",Tahoma,Arial,"Noto Sans Arabic","Cairo",sans-serif;
        overflow-x:auto; overflow-y:auto; max-width:100%; max-height:80vh; padding:8px;
    }
    .org-chart>ul{ display:inline-flex; min-width:max-content; justify-content:flex-start; }

    .org-level{ position:relative; margin:16px 0; }
    .org-node{ display:flex; flex-direction:column; align-items:center; position:relative; }

    /* siblings side by side (default) */
    .org-level.children-row{
        display:flex; justify-content:center; align-items:flex-start; gap:16px; flex-wrap:nowrap; padding:0 8px;
    }
    .org-level.children-row::before{
        content:''; position:absolute; top:-14px; left:0; right:0; height:1px; background:#94a3b8;
    }

    /* children stacked vertically (for reps) */
    .org-level.children-col{ display:flex; flex-direction:column; align-items:center; gap:10px; }
    .org-level.children-col::before{ display:none; }

    /* connector from parent down */
    .org-node::before{
        content:''; position:absolute; top:-14px; left:50%; width:1px; height:14px; background:#94a3b8;
    }
    .org-level:first-child>.org-node::before{ display:none; }

    /* ===== Node box (compact) ===== */
    .org-box{
        border:1px solid #dfe5ee; border-radius:8px; background:#fff;
        min-width:130px; max-width:170px; text-align:center;
        box-shadow:0 2px 6px rgba(0,0,0,.06); overflow:hidden;
    }
    .org-role{ padding:6px 8px; font-size:.78rem; font-weight:700; color:#fff; }
    .org-name{
        padding:8px 10px; font-size:.9rem; color:#1f2937; background:#f8fafc; line-height:1.25;
        display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; word-break:break-word;
    }

    /* reps more compact */
    .org-node.is-rep .org-box{ min-width:110px; max-width:140px; }
    .org-node.is-rep .org-role{ padding:5px 7px; font-size:.72rem; }
    .org-node.is-rep .org-name{ padding:7px 8px; font-size:.85rem; }

    /* role colors */
    .role-sales-manager{ background:#6f42c1; }       /* purple */
    .role-area-manager{ background:#1976f3; }         /* blue   */
    .role-sales-supervisor{ background:#20c997; }     /* teal   */
    .role-sales-rep{ background:#9bb6d1; }            /* soft gray-blue */

    /* ---------- RESPONSIVE: vertical layout on narrow screens ---------- */
    @media (max-width:1500px){
        .org-level.children-row{ flex-direction:column; align-items:center; gap:10px; }
        .org-level.children-row::before{ display:none; }
        .org-chart{ max-height:none; overflow-x:hidden; overflow-y:auto; }
    }

    /* ---------- AUTO-COMPACT when a node has >5 children ---------- */
    /* this class is added to the UL that wraps the children */
    .children-dense .org-node .org-box{ min-width:100px; max-width:120px; }
    .children-dense .org-node .org-role{ padding:4px 6px; font-size:.68rem; }
    .children-dense .org-node .org-name{ padding:6px 7px; font-size:.8rem; -webkit-line-clamp:2; }

    /* still keep reps slightly smaller overall */
    .org-node.is-rep .org-box{ min-width:100px; max-width:120px; }
    .org-node.is-rep .org-role{ padding:4px 6px; font-size:.7rem; }
    .org-node.is-rep .org-name{ padding:6px 7px; font-size:.82rem; }
</style>

<div class="contentbar">
    <div class="row">
        <div class="col-lg-12">
            <div class="card m-b-30">
                <div class="card-body">
                    <div class="org-chart">
                        <ul class="list-unstyled org-level children-row">
                            @foreach ($salesManagers as $user)
                                @include('users.org-node', ['node' => $user])
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('layouts.footer')
