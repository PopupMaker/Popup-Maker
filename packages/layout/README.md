# @popup-maker/layout

Shared layout components for Popup Maker admin interfaces.

## Description

This package provides a unified layout system for all Popup Maker admin pages, including:
- Consistent header with branding, navigation tabs, and support dropdown
- Extensible layout components using WordPress SlotFill pattern
- Type-safe TypeScript components

## Usage

```tsx
import { AppLayout, AppHeader, AppContent } from '@popup-maker/layout';

const MyAdminPage = () => {
  const tabs = [
    {
      name: 'dashboard',
      title: 'Dashboard',
      className: 'dashboard-tab',
    },
    {
      name: 'settings',
      title: 'Settings',
      className: 'settings-tab',
    },
  ];

  return (
    <AppLayout>
      <AppHeader
        title="My Admin Page"
        tabs={tabs}
        currentTab="dashboard"
        onTabChange={(tabName) => console.log(tabName)}
      />
      <AppContent>
        <h2>Page Content</h2>
      </AppContent>
    </AppLayout>
  );
};
```

## SlotFill Extensions

The layout system supports extensions via SlotFill:

```tsx
import { HeaderActionsFill } from '@popup-maker/layout';

const MyHeaderAction = () => (
  <HeaderActionsFill>
    <button>Custom Action</button>
  </HeaderActionsFill>
);
```

### Available Slots

- `HeaderStartFill` - Add items to the start of the header
- `HeaderEndFill` - Add items to the end of the header
- `HeaderActionsFill` - Add action buttons before the support dropdown
- `SupportMenuFill` - Add items to the support dropdown menu

## Components

### AppLayout
Main layout wrapper that provides the overall structure.

### AppHeader
Unified header component with:
- Popup Maker branding
- Tab navigation
- Support dropdown
- Extensible via SlotFill

### AppContent
Content wrapper with consistent styling and spacing.