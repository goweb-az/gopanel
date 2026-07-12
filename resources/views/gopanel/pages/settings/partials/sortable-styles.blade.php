<style>
    .sortable-shell {
        min-height: 8rem;
        position: relative;
    }

    .sortable-overlay {
        align-items: center;
        background: rgb(255 255 255 / 72%);
        display: flex;
        inset: 0;
        justify-content: center;
        position: absolute;
        z-index: 20;
    }

    .drag-handle,
    .menu-drag-handle {
        background: transparent;
        border: 0;
        color: #74788d;
        cursor: grab;
        padding: .35rem .5rem;
    }

    .drag-handle:active,
    .menu-drag-handle:active {
        cursor: grabbing;
    }

    .menu-sortable {
        list-style: none;
        margin: 0;
        min-height: .75rem;
        padding-left: 1.5rem;
    }

    #menu-tree > .menu-sortable {
        padding-left: 0;
    }

    .menu-node-card {
        align-items: center;
        background: #fff;
        border: 1px solid #e9ecef;
        border-radius: .5rem;
        display: flex;
        gap: .75rem;
        margin-bottom: .5rem;
        padding: .6rem .75rem;
    }

    .menu-node-inactive {
        opacity: .6;
    }

    .menu-node-actions {
        display: flex;
        gap: .25rem;
    }

    .menu-drop-placeholder {
        background: #eef2ff;
        border: 2px dashed #556ee6;
        border-radius: .5rem;
        list-style: none;
        min-height: 3rem;
    }
</style>
