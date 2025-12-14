#!/bin/bash

echo "=========================================="
echo "POS System - Setup Script"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Step 1: Running migrations...${NC}"
php artisan migrate

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migrations completed successfully!${NC}"
    echo ""
else
    echo -e "${RED}✗ Migration failed!${NC}"
    exit 1
fi

echo -e "${YELLOW}Step 2: Would you like to seed sample data? (y/n)${NC}"
read -r response

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo -e "${YELLOW}Seeding sample data...${NC}"
    php artisan db:seed --class=PosSeeder
    
    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Sample data seeded successfully!${NC}"
        echo ""
        echo "Sample data created:"
        echo "  - 3 Categories (Momo, Wings, Drinks)"
        echo "  - 5 Ingredients with initial stock"
        echo "  - 5 Menu items (3 with variants)"
        echo ""
    else
        echo -e "${RED}✗ Seeding failed!${NC}"
    fi
fi

echo -e "${YELLOW}Step 3: Clearing cache...${NC}"
php artisan cache:clear
php artisan config:clear

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Cache cleared!${NC}"
    echo ""
else
    echo -e "${RED}✗ Cache clear failed!${NC}"
fi

echo -e "${GREEN}=========================================="
echo "Setup Complete!"
echo "==========================================${NC}"
echo ""
echo "Next steps:"
echo "  1. Check database tables are created"
echo "  2. Visit your application in browser"
echo "  3. Start testing the POS endpoints"
echo ""
echo "Documentation:"
echo "  - POS_README.md (Complete API documentation)"
echo "  - SETUP_GUIDE.md (Quick start guide)"
echo "  - IMPLEMENTATION_SUMMARY.md (What was built)"
echo ""
echo "Test the system:"
echo "  php artisan test --filter PosSystemTest"
echo ""
echo -e "${GREEN}Happy coding! 🚀${NC}"
