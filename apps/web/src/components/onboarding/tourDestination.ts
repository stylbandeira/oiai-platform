export function getListTourDestination(lists: Array<{ id: number; status: string }>) {
  const active = lists.find((list) => list.status === "active");
  return active
    ? { path: `/list/${active.id}`, state: { showOptimizeTour: true } }
    : { path: "/new-list", state: { showOptimizeTourAfterCreate: true } };
}

export function moveTourStep(current: number, direction: -1 | 1, total: number): number {
  return Math.max(0, Math.min(total - 1, current + direction));
}
