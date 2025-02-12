# Extending CTA Editor Filters

The CTA Editor uses a registry pattern for filters, allowing Pro and Ecommerce plugins to extend the filtering functionality. This document explains how to add new filters to the CTA Editor.

## Overview

The filter registry system allows you to:

- Add new filters to the CTA Editor
- Define custom filter types with proper TypeScript support
- Group filters by priority
- Maintain type safety across the entire filtering system

## Adding a New Filter

To add a new filter, use the `registerListFilter` function:

```typescript
import { registerListFilter } from '@popup-maker/cta-editor';

// Define your filter's types
type MyFilterKey = 'myFilter';
type MyFilterValue = string;

// Create your filter component
const MyFilter: React.FC<ListFilterContext<MyFilterKey, MyFilterValue>> = ({
    filters,
    setFilters,
    onClose,
    items,
    filteredItems,
}) => {
    // Your filter implementation
};

// Register your filter
registerListFilter({
    id: 'my-filter',
    group: 'advanced', // 'core' or 'advanced'
    render: MyFilter,
});
```

## Filter Groups

Filters can be organized into two groups:

- `core`: Priority 10 - For essential filters
- `advanced`: Priority 20 - For additional functionality

## TypeScript Support

The registry provides full TypeScript support:

```typescript
import type { ListFilterContext } from '@popup-maker/cta-editor';

// Define your filter's types
type MyFilterKey = 'myFilter';
type MyFilterValue = string | number;

// Use the types in your component
const MyFilter: React.FC<ListFilterContext<MyFilterKey, MyFilterValue>> = ...
```

## Example: Adding a Price Filter

Here's a complete example of adding a price filter for the Ecommerce plugin:

```typescript
import { registerListFilter } from '@popup-maker/cta-editor';
import type { ListFilterContext } from '@popup-maker/cta-editor';

type PriceFilterKey = 'price';
type PriceFilterValue = {
    min: number;
    max: number;
};

const PriceFilter: React.FC<ListFilterContext<PriceFilterKey, PriceFilterValue>> = ({
    filters,
    setFilters,
    onClose,
    items,
    filteredItems,
}) => {
    return (
        // Your price filter UI implementation
    );
};

registerListFilter({
    id: 'price-filter',
    group: 'advanced',
    render: PriceFilter,
});
```

## Best Practices

1. Always define proper TypeScript types for your filters
2. Use the `advanced` group for plugin-specific filters
3. Follow the existing filter UI patterns for consistency
4. Implement proper filter state management
5. Add unit tests for your filters

## Available Filter Props

The `ListFilterContext` provides these props to your filter component:

- `filters`: Current filter values
- `setFilters`: Function to update filters
- `onClose`: Callback when filter UI should close
- `items`: All available items
- `filteredItems`: Items after other filters are applied

## Testing

When adding new filters, ensure you add proper unit tests:

```typescript
import { render, screen, fireEvent } from '@testing-library/react';

describe('MyFilter', () => {
    it('filters items correctly', () => {
        // Your test implementation
    });
});
```
