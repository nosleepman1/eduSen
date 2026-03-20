import { useState } from 'react';
import { Loader2, Search } from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { EmptyState } from './EmptyState';
import { hasNextPage, hasPreviousPage, type PaginationMeta } from '@/lib/api.types';

export interface DataTableColumn<T> {
  key: string;
  header: string;
  render: (row: T) => React.ReactNode;
  className?: string;
}

interface DataTableProps<T> {
  columns: DataTableColumn<T>[];
  rows: T[];
  rowKey: (row: T) => string;
  isLoading?: boolean;
  meta?: PaginationMeta | null;
  onPageChange?: (page: number) => void;
  /** Debounced by the caller if needed — this component fires on every change. */
  onSearchChange?: (value: string) => void;
  searchPlaceholder?: string;
  emptyTitle?: string;
  emptyDescription?: string;
  /** Extra controls (status filter, "Nouveau X" button) rendered next to search. */
  toolbarExtra?: React.ReactNode;
}

/**
 * Generic list screen: search, server-side pagination, loading skeleton, empty
 * state. Each domain screen supplies only its columns and its react-query hook
 * — the goal is one shared layout across the ~13 domains instead of one
 * bespoke table per screen.
 */
export function DataTable<T>({
  columns,
  rows,
  rowKey,
  isLoading = false,
  meta = null,
  onPageChange,
  onSearchChange,
  searchPlaceholder = 'Rechercher...',
  emptyTitle = 'Aucun résultat',
  emptyDescription,
  toolbarExtra,
}: DataTableProps<T>) {
  const [search, setSearch] = useState('');

  const handleSearch = (value: string) => {
    setSearch(value);
    onSearchChange?.(value);
  };

  return (
    <div className="space-y-4">
      {(onSearchChange || toolbarExtra) && (
        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
          {onSearchChange ? (
            <div className="relative w-full sm:max-w-xs">
              <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
              <Input
                value={search}
                onChange={(e) => handleSearch(e.target.value)}
                placeholder={searchPlaceholder}
                className="pl-8"
              />
            </div>
          ) : (
            <div />
          )}
          {toolbarExtra}
        </div>
      )}

      <Card className="shadow-sm">
        <CardContent className="p-0">
          <div className="overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow>
                  {columns.map((col) => (
                    <TableHead key={col.key} className={col.className}>
                      {col.header}
                    </TableHead>
                  ))}
                </TableRow>
              </TableHeader>
              <TableBody>
                {isLoading ? (
                  <TableRow>
                    <TableCell colSpan={columns.length} className="py-14 text-center">
                      <Loader2 className="mx-auto h-5 w-5 animate-spin text-muted-foreground" />
                    </TableCell>
                  </TableRow>
                ) : rows.length === 0 ? (
                  <TableRow>
                    <TableCell colSpan={columns.length} className="p-0">
                      <EmptyState title={emptyTitle} description={emptyDescription} />
                    </TableCell>
                  </TableRow>
                ) : (
                  rows.map((row) => (
                    <TableRow key={rowKey(row)}>
                      {columns.map((col) => (
                        <TableCell key={col.key} className={col.className}>
                          {col.render(row)}
                        </TableCell>
                      ))}
                    </TableRow>
                  ))
                )}
              </TableBody>
            </Table>
          </div>
        </CardContent>
      </Card>

      {meta && onPageChange && rows.length > 0 && (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
          <span>
            {meta.total !== undefined
              ? `${meta.from ?? 0}–${meta.to ?? 0} sur ${meta.total}`
              : `Page ${meta.current_page}`}
          </span>
          <div className="flex gap-2">
            <Button
              variant="outline"
              size="sm"
              disabled={!hasPreviousPage(meta)}
              onClick={() => onPageChange(meta.current_page - 1)}
            >
              Précédent
            </Button>
            <Button
              variant="outline"
              size="sm"
              disabled={!hasNextPage(meta)}
              onClick={() => onPageChange(meta.current_page + 1)}
            >
              Suivant
            </Button>
          </div>
        </div>
      )}
    </div>
  );
}
