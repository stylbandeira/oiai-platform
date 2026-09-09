import { useParams } from "react-router-dom";
import NewShoppingList from "./NewShoppingList";

export default function EditShoppingList() {
    const { listId } = useParams<{ listId: string }>();

    if (!listId) {
        return (
            <div className="container mx-auto px-4 py-6">
                <div className="text-center py-8">
                    <h2 className="text-xl font-semibold mb-2">Lista não encontrada</h2>
                    <p className="text-muted-foreground">O ID da lista não foi fornecido.</p>
                </div>
            </div>
        );
    }

    return (
        <NewShoppingList
            isEditMode={true}
            listId={parseInt(listId)}
        />
    );
}
