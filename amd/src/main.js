import Reactive from 'mod_kanbanccead/reactive';
import KanbanCceadParent from 'mod_kanbanccead/kanbancceadparent';
import KanbanCceadMutations from 'mod_kanbanccead/mutations';

const stateChangedEventName = 'mod_kanbanccead:stateChanged';

/**
 * Create reactive instance for kanbanccead, load initial state.
 * @param {string} domElementId Id of render container
 * @param {number} cmId Course module id of the kanbanccead board
 * @param {number} boardId Id of the board to display
 * @returns {KanbanCceadComponent}
 */
export const init = (domElementId, cmId, boardId) => {
    const reactiveInstance = new Reactive({
        name: 'kanbanccead_' + cmId,
        eventName: stateChangedEventName,
        eventDispatch: dispatchKanbanCceadEvent,
        target: document.getElementById(domElementId),
        mutations: new KanbanCceadMutations(),
    });
    reactiveInstance.loadBoard(cmId, boardId);
    return new KanbanCceadParent({
        element: document.getElementById(domElementId),
        reactive: reactiveInstance,
    });
};

/**
 * Internal state changed event.
 *
 * @method dispatchKanbanCceadEvent
 * @param {object} detail the full state
 * @param {object} target the custom event target (document if none provided)
 */
function dispatchKanbanCceadEvent(detail, target) {
    if (target === undefined) {
        target = document;
    }
    target.dispatchEvent(
        new CustomEvent(
            stateChangedEventName,
            {
                bubbles: true,
                detail: detail,
            }
        )
    );
}