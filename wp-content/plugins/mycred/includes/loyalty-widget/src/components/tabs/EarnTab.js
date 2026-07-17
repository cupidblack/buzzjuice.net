import { useState } from '@wordpress/element';
import { Box, Typography, Paper, Chip, LinearProgress, IconButton } from '@mui/material';
import ArrowBackIcon from '@mui/icons-material/ArrowBack';
import CloseIcon from '@mui/icons-material/Close';
import { __ } from '@wordpress/i18n';

export default function EarnTab({ settings, currentContent, user, activeHooks, onBack, onClose }) {
    currentContent = currentContent || {};
    const [selectedFilter, setSelectedFilter] = useState('all');
    const eventtriggers = settings.eventtriggers || {};
    const design = settings.design || {};
    const btnColor = design.buttonColor || '#5E2CED';
    const bgColor = design.backgroundColor || '#2D1572';

    // Helper to find custom label from settings
    const getHookLabel = (hook) => {
        const cat = hook.category ? `${hook.category}Hooks` : 'wordpressHooks';
        let label = hook.title;
        
        if (settings.eventtriggers && settings.eventtriggers[cat] && Array.isArray(settings.eventtriggers[cat])) {
            const match = settings.eventtriggers[cat].find(h => String(h.id) === String(hook.id) && String(h.point_type) === String(hook.point_type));
            if (match && match.displayLabel) {
                label = match.displayLabel;
            }
        }
        
        // Replace placeholders
        if (label) {
            label = label.replace(/%plural%/g, hook.plural || __('points', 'mycred'));
            label = label.replace(/%singular%/g, hook.singular || __('point', 'mycred'));
        }

        return label;
    };

    // Get category for hook
    const getHookCategory = (hook) => {
        if (hook.id.includes('woocommerce')) return 'woocommerce';
        if (hook.id.includes('buddypress') || hook.id.includes('bp_')) return 'buddypress';
        if (hook.id.includes('bbpress') || hook.id.includes('forum')) return 'forum';
        return 'wordpress';
    };

    // Helper to check if hook is enabled
    const isHookEnabled = (hook) => {
        const categories = ['wordpressHooks', 'woocommerceHooks', 'buddypressHooks', 'forumHooks'];
        for (const cat of categories) {
            if (eventtriggers[cat] && Array.isArray(eventtriggers[cat])) {
                const match = eventtriggers[cat].find(h => String(h.id) === String(hook.id) && String(h.point_type) === String(hook.point_type));
                if (match) {
                    return match.enabled !== false; // Default to true if not explicitly set
                }
            }
        }
        return true; // Default to enabled if not found in settings
    };

    // Filter hooks - only show enabled ones
    const filteredHooks = (selectedFilter === 'all'
        ? activeHooks
        : activeHooks.filter(hook => getHookCategory(hook) === selectedFilter))
        .filter(hook => isHookEnabled(hook));

    if (eventtriggers.enableHooks === false) {
        return (
            <Box sx={{ p: 4, textAlign: 'center' }}>
                <Typography sx={{ color: '#666' }}>{__('No earning opportunities available at the moment.', 'mycred')}</Typography>
            </Box>
        );
    }

    const filters = [
        { id: 'all', label: __('All', 'mycred'), icon: '⚡' },
        { id: 'wordpress', label: __('WordPress', 'mycred'), icon: '📝' },
        { id: 'woocommerce', label: __('WooCommerce', 'mycred'), icon: '🛒' },
        { id: 'buddypress', label: __('BuddyPress', 'mycred'), icon: '👥' },
        { id: 'forum', label: __('Third-party', 'mycred'), icon: '🔗' }
    ];

    return (
        <Box sx={{ display: 'flex', flexDirection: 'column', height: '100%', bgcolor: bgColor }}>
            {/* Header with Back and Close buttons */}
            <Box sx={{ p: '20px 24px', color: '#fff', display: 'flex', alignItems: 'center', justifyContent: 'space-between' }}>
                <Box sx={{ display: 'flex', alignItems: 'center', gap: 1 }}>
                    <IconButton size="small" onClick={onBack} sx={{ color: '#fff', p: 0.5 }}>
                        <ArrowBackIcon />
                    </IconButton>
                    <Box>
                        <Typography sx={{ fontSize: '18px', fontWeight: 700 }}>
                            {currentContent.earnMessage || __('Earn', 'mycred')}
                        </Typography>
                    </Box>
                </Box>
                <IconButton size="small" onClick={onClose} sx={{ color: '#fff' }}>
                    <CloseIcon />
                </IconButton>
            </Box>

            {/* Filters */}
            <Box sx={{ px: '24px', pb: 2, display: 'flex', gap: 1, flexWrap: 'wrap' }}>
                {filters.map(filter => (
                    <Chip
                        key={filter.id}
                        label={`${filter.icon} ${filter.label}`}
                        onClick={() => setSelectedFilter(filter.id)}
                        sx={{
                            bgcolor: selectedFilter === filter.id ? 'rgba(255,255,255,0.25)' : 'rgba(255,255,255,0.1)',
                            color: '#fff',
                            fontSize: '12px',
                            height: '28px',
                            '&:hover': { bgcolor: 'rgba(255,255,255,0.2)' }
                        }}
                    />
                ))}
            </Box>

            {/* Content */}
            <Box sx={{
                flex: 1, bgcolor: '#F8F9FB', borderRadius: '24px 24px 0 0', p: '20px', overflowY: 'auto',
                scrollbarWidth: 'none', '&::-webkit-scrollbar': { display: 'none' }
            }}>

                {filteredHooks.length > 0 && (
                    <Typography sx={{ fontSize: '12px', fontWeight: 600, color: '#666', mb: 2, textTransform: 'uppercase' }}>
                        {selectedFilter === 'all' ? __('All Challenges', 'mycred') : filters.find(f => f.id === selectedFilter)?.label}
                    </Typography>
                )}

                <Box sx={{ 
                    display: 'flex', flexDirection: 'column', gap: 2,
                    '& > *': {
                        animation: 'slideKeyframe 0.5s cubic-bezier(0.16, 1, 0.3, 1) both',
                    },
                    ...Array.from({ length: 15 }).reduce((acc, _, i) => ({
                        ...acc,
                        [`& > *:nth-of-type(${i + 1})`]: { animationDelay: `${i * 0.05}s` }
                    }), {})
                }}>
                    {filteredHooks.map((hook, index) => (
                        <Paper
                            key={`${hook.id}-${hook.point_type}-${index}`}
                            elevation={0}
                            sx={{
                                p: 2,
                                borderRadius: '12px',
                                border: '1px solid #E8E8E8',
                                bgcolor: '#fff'
                            }}
                        >
                            <Box sx={{ display: 'flex', alignItems: 'flex-start', gap: 2 }}>
                                <Box sx={{
                                    width: 40,
                                    height: 40,
                                    borderRadius: '10px',
                                    bgcolor: `${btnColor}22`,
                                    display: 'flex',
                                    alignItems: 'center',
                                    justifyContent: 'center',
                                    flexShrink: 0
                                }}>
                                    <Typography sx={{ fontSize: '20px' }}>⭐</Typography>
                                </Box>
                                <Box sx={{ flex: 1 }}>
                                    <Typography sx={{ fontSize: '14px', fontWeight: 600, color: '#1a1a1a', mb: 0.5 }}>
                                        {getHookLabel(hook)}
                                    </Typography>
                                    <Typography sx={{ fontSize: '12px', color: '#666' }}>
                                        {__('Earn points by participating', 'mycred')}
                                    </Typography>
                                </Box>
                                <Typography sx={{ 
                                    fontSize: '14px', 
                                    fontWeight: 700, 
                                    color: String(hook.formatted_amount || hook.amount || '0').startsWith('-') ? '#EF4444' : '#38A169', 
                                    whiteSpace: 'nowrap',
                                    bgcolor: String(hook.formatted_amount || hook.amount || '0').startsWith('-') ? '#FEE2E2' : 'transparent',
                                    px: String(hook.formatted_amount || hook.amount || '0').startsWith('-') ? '8px' : 0,
                                    py: String(hook.formatted_amount || hook.amount || '0').startsWith('-') ? '2px' : 0,
                                    borderRadius: '100px'
                                }}>
                                    {(String(hook.formatted_amount || hook.amount || '0').startsWith('-') || String(hook.formatted_amount || hook.amount || '0').startsWith('+')) ? '' : '+'}{hook.formatted_amount || hook.amount || '0'} {user?.all_balances?.find(b => b.type === hook.point_type)?.label || user?.point_label || ''}
                                </Typography>
                            </Box>
                        </Paper>
                    ))}

                    {filteredHooks.length === 0 && (
                        <Box sx={{ textAlign: 'center', py: 4 }}>
                            <Typography sx={{ color: '#666', fontSize: '14px' }}>
                                {__('No rewards found for this filter.', 'mycred')}
                            </Typography>
                        </Box>
                    )}
                </Box>
            </Box>
        </Box>
    );
}
