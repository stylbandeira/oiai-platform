import { useEffect, useState } from "react";
import { ImageIcon } from "lucide-react";

import { Badge } from "@/components/ui/badge";
import { Checkbox } from "@/components/ui/checkbox";

interface ShoppingListProductRowProps {
  name: string;
  image: string | null;
  quantity: number;
  unity: string;
  category: string;
  unitPrice: number;
  completed: boolean;
  canComplete: boolean;
  onToggleComplete: () => void;
}

const getProductImageUrl = (image: string | null) => {
  if (!image) return null;
  if (/^https?:\/\//i.test(image) || image.startsWith("data:") || image.startsWith("blob:")) {
    return image;
  }

  const apiUrl = import.meta.env.VITE_API_URL?.replace(/\/$/, "");
  if (!apiUrl) return image.startsWith("/") ? image : `/storage/${image}`;

  const path = image.replace(/^\/?storage\//, "").replace(/^\//, "");
  return `${apiUrl}/storage/${path}`;
};

export function ShoppingListProductRow({
  name,
  image,
  quantity,
  unity,
  category,
  unitPrice,
  completed,
  canComplete,
  onToggleComplete,
}: ShoppingListProductRowProps) {
  const imageUrl = getProductImageUrl(image);
  const [hasImageError, setHasImageError] = useState(false);

  useEffect(() => setHasImageError(false), [imageUrl]);

  return (
    <div
      className={`flex items-center gap-3 rounded-xl p-3 transition-all sm:gap-4 sm:p-4 ${
        completed
          ? "bg-gray-50 opacity-75"
          : "border border-gray-100 bg-white hover:shadow-md"
      }`}
    >
      {canComplete && (
        <Checkbox
          checked={completed}
          onCheckedChange={onToggleComplete}
          className="flex-shrink-0"
          aria-label={`Marcar ${name} como ${completed ? "não concluído" : "concluído"}`}
        />
      )}

      <div className="h-14 w-14 flex-shrink-0 overflow-hidden rounded-lg border bg-slate-50 sm:h-16 sm:w-16">
        {imageUrl && !hasImageError ? (
          <img
            src={imageUrl}
            alt={name}
            className="h-full w-full object-contain p-1"
            loading="lazy"
            onError={() => setHasImageError(true)}
          />
        ) : (
          <div className="flex h-full w-full items-center justify-center" aria-hidden="true">
            <ImageIcon className="h-6 w-6 text-slate-300" />
          </div>
        )}
      </div>

      <div className="min-w-0 flex-1">
        <h4 className={`truncate font-semibold ${completed ? "line-through text-muted-foreground" : ""}`}>
          {name}
        </h4>
        <div className="mt-1 flex flex-wrap items-center gap-2">
          <span className="text-sm text-muted-foreground">
            {quantity} {unity}
          </span>
          <Badge variant="outline" className="text-xs">
            {category}
          </Badge>
        </div>
      </div>

      <div className="flex-shrink-0 text-right">
        <p className="font-bold text-primary">R$ {(unitPrice * quantity).toFixed(2)}</p>
        <p className="text-xs text-muted-foreground">R$ {unitPrice.toFixed(2)}/{unity}</p>
      </div>
    </div>
  );
}
